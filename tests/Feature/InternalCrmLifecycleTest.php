<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InternalCrmLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendly_booking_updates_matching_lead_booking_status_by_email(): void
    {
        Mail::fake();

        $lead = Lead::create($this->leadData([
            'email' => 'buyer@example.com',
            'booking_status' => 'NotBooked',
        ]));

        $response = $this->postJson(route('webhooks.calendly'), [
            'event' => 'invitee.created',
            'payload' => [
                'uri' => 'https://api.calendly.com/scheduled_events/invitees/invitee-1',
                'name' => 'Buyer Contact',
                'email' => 'buyer@example.com',
                'event_type' => ['name' => 'Discovery Call'],
                'scheduled_event' => [
                    'uri' => 'https://api.calendly.com/scheduled_events/event-1',
                    'start_time' => now()->addDay()->toIso8601String(),
                    'end_time' => now()->addDay()->addHour()->toIso8601String(),
                    'location' => ['join_url' => 'https://meet.example.test/abc'],
                ],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('bookings', [
            'client_email' => 'buyer@example.com',
            'lead_id' => $lead->id,
            'status' => 'active',
        ]);
        $this->assertSame('Booked', $lead->fresh()->booking_status);
    }

    public function test_booking_page_passes_lead_token_to_calendly_tracking(): void
    {
        $response = $this->get(route('booking.index', [
            'name' => 'Buyer Contact',
            'email' => 'buyer@example.com',
            'booking_token' => 'booking-token-123',
        ]));

        $response->assertOk();
        $response->assertSee('name=Buyer+Contact', false);
        $response->assertSee('email=buyer%40example.com', false);
        $response->assertSee('utm_content=booking-token-123', false);
    }

    public function test_calendly_booking_links_to_lead_by_booking_token_when_email_differs(): void
    {
        Mail::fake();

        $lead = Lead::create($this->leadData([
            'email' => 'snapshot@example.com',
            'booking_token' => 'lead-token-123',
            'booking_status' => 'NotBooked',
        ]));
        $otherLead = Lead::create($this->leadData([
            'email' => 'invitee@example.com',
            'booking_token' => 'other-token-123',
            'booking_status' => 'NotBooked',
        ]));

        $response = $this->postJson(route('webhooks.calendly'), [
            'event' => 'invitee.created',
            'payload' => [
                'uri' => 'https://api.calendly.com/scheduled_events/invitees/invitee-token',
                'name' => 'Buyer Contact',
                'email' => 'invitee@example.com',
                'tracking' => ['utm_content' => 'lead-token-123'],
                'event_type' => ['name' => 'Discovery Call'],
                'scheduled_event' => [
                    'uri' => 'https://api.calendly.com/scheduled_events/event-token',
                    'start_time' => now()->addDay()->toIso8601String(),
                    'end_time' => now()->addDay()->addHour()->toIso8601String(),
                    'location' => ['join_url' => 'https://meet.example.test/token'],
                ],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('bookings', [
            'client_email' => 'invitee@example.com',
            'lead_id' => $lead->id,
            'status' => 'active',
        ]);
        $this->assertSame('Booked', $lead->fresh()->booking_status);
        $this->assertSame('NotBooked', $otherLead->fresh()->booking_status);
    }

    public function test_calendly_cancellation_resets_only_when_linked_lead_has_no_active_booking(): void
    {
        Mail::fake();

        $lead = Lead::create($this->leadData([
            'email' => 'cancel@example.com',
            'booking_token' => 'cancel-token-123',
            'booking_status' => 'Booked',
        ]));

        Booking::create([
            'lead_id' => $lead->id,
            'calendly_event_uuid' => 'event-cancel',
            'calendly_invitee_uuid' => 'invitee-cancel',
            'client_name' => 'Buyer Contact',
            'client_email' => 'different@example.com',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'active',
        ]);

        $response = $this->postJson(route('webhooks.calendly'), [
            'event' => 'invitee.canceled',
            'payload' => [
                'scheduled_event' => [
                    'uri' => 'https://api.calendly.com/scheduled_events/event-cancel',
                ],
                'cancellation' => ['reason' => 'Client unavailable'],
            ],
        ]);

        $response->assertOk();
        $this->assertSame('NotBooked', $lead->fresh()->booking_status);
    }

    public function test_starting_paid_assessment_from_lead_converts_lead_to_client(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $lead = Lead::create($this->leadData([
            'email' => 'convert@example.com',
            'company' => 'Convert Ltd',
            'converted_to_client' => false,
        ]));

        $response = $this->actingAs($admin)->post(route('admin.assessments.store'), [
            'snapshot_submission_id' => $lead->id,
            'type' => 'PIR',
            'report_tier' => 'Tier 1 Rapid',
            'name' => 'Tier 1 for Convert Ltd',
            'target_entity' => 'ERP Programme',
        ]);

        $response->assertRedirect();
        $lead->refresh();

        $this->assertTrue($lead->converted_to_client);
        $this->assertSame('Hot', $lead->lead_status);
        $this->assertNotNull($lead->client_id);
        $this->assertDatabaseHas('clients', [
            'id' => $lead->client_id,
            'company_name' => 'Convert Ltd',
            'email' => 'convert@example.com',
        ]);
        $this->assertDatabaseHas('assessments', [
            'client_id' => $lead->client_id,
            'snapshot_submission_id' => $lead->id,
            'report_tier' => 'Tier 1 Rapid',
        ]);
    }

    public function test_admin_can_update_lead_booking_status_and_notes_inline(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $lead = Lead::create($this->leadData());

        $response = $this->actingAs($admin)->patchJson(route('admin.leads.update', $lead), [
            'booking_status' => 'BookingRequested',
            'note' => 'Call requested after snapshot review.',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'booking_status' => 'BookingRequested',
            'note' => 'Call requested after snapshot review.',
        ]);
    }

    public function test_admin_can_export_filtered_leads_to_csv(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        Lead::create($this->leadData(['company' => 'Export Ltd', 'priority' => 'High']));
        Lead::create($this->leadData(['company' => 'Ignore Ltd', 'priority' => 'Low', 'email' => 'ignore@example.com']));

        $response = $this->actingAs($admin)->get(route('admin.leads.export', ['priority' => 'High']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Export Ltd', $csv);
        $this->assertStringNotContainsString('Ignore Ltd', $csv);
    }

    private function leadData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Buyer Contact',
            'company' => 'Buyer Ltd',
            'email' => 'buyer@example.com',
            'phone' => '+441234567890',
            'type' => 'PIR',
            'assessment_type' => 'PIR_SNAPSHOT',
            'source' => 'Assessment',
            'overall_score' => 2.8,
            'rag_status' => 'Red',
            'priority' => 'High',
            'lead_status' => 'Hot',
            'booking_status' => 'NotBooked',
            'booking_token' => fake()->uuid(),
            'converted_to_client' => false,
            'consent_given' => true,
            'consent_timestamp' => now(),
        ], $overrides);
    }
}
