<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class InternalCrmService
{
    public function __construct(private DiagnosticOutcomeService $outcomes)
    {
    }

    public function createSnapshotLead(
        array $validatedData,
        string $type,
        array $results,
        array $answers,
        array $confidence,
        array $context,
        Carbon $consentTimestamp,
        ?string $aiRecommendation = null
    ): Lead {
        $pillarScores = $results['pillar_scores'] ?? [];
        $overallScore = (float) ($results['overall_score'] ?? 0);
        $leadPriority = $this->outcomes->leadPriority($overallScore, $pillarScores);
        $criticalFlag = $this->outcomes->criticalFlag($pillarScores) ? 'CriticalAreaBelowThreshold' : null;
        $framework = strtoupper($type);

        return Lead::create([
            'name' => $validatedData['name'],
            'company' => $validatedData['company'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'] ?? null,
            'role_title' => $validatedData['job_title'],
            'industry' => $validatedData['industry'] ?? 'Other',
            'regulatory_context' => $context['regulatory_context'] ?? null,
            'scoring_version' => '1.0',
            'delivery_stage' => $context['delivery_stage'] ?? null,
            'service_context' => $context['service_context'] ?? null,
            'type' => $framework,
            'assessment_type' => "{$framework}_SNAPSHOT",
            'source' => 'Assessment',
            'overall_score' => round($overallScore, 2),
            'rag_status' => $results['rag_status'] ?? 'Amber',
            'critical_flag' => $criticalFlag,
            'action_indicator' => $this->outcomes->actionIndicator($overallScore),
            'alerts_json' => $this->outcomes->alerts($pillarScores),
            'top_three_insight_areas_json' => $this->outcomes->topThreeInsightAreas($pillarScores),
            'priority' => $leadPriority,
            'lead_status' => $this->salesStatusFor($leadPriority),
            'booking_status' => 'NotBooked',
            'booking_token' => (string) Str::uuid(),
            'index_scores_json' => $results['index_scores'] ?? [],
            'answers_json' => $answers,
            'confidence_json' => $confidence,
            'converted_to_client' => false,
            'consent_given' => true,
            'consent_timestamp' => $consentTimestamp,
            'ai_recommendation' => $aiRecommendation,
        ]);
    }

    public function salesStatusFor(string $priority): string
    {
        return match ($priority) {
            'High' => 'Hot',
            'Medium' => 'Warm',
            default => 'Cold',
        };
    }

    public function syncLeadForBooking(Booking $booking, ?string $bookingToken = null): void
    {
        $lead = $this->findLeadByBookingToken($bookingToken)
            ?? $booking->lead
            ?? $this->findLeadByEmail($booking->client_email);

        if ($lead) {
            if ($booking->lead_id !== $lead->id) {
                $booking->update(['lead_id' => $lead->id]);
            }

            $lead->update(['booking_status' => 'Booked']);
        }
    }

    public function syncLeadForCancelledBooking(Booking $booking): void
    {
        $lead = $booking->lead ?? $this->findLeadByEmail($booking->client_email);

        if (! $lead) {
            return;
        }

        $hasActiveBooking = Booking::query()
            ->where(function ($query) use ($lead, $booking) {
                $query->where('lead_id', $lead->id);

                if ($booking->client_email) {
                    $query->orWhereRaw('lower(client_email) = ?', [strtolower($booking->client_email)]);
                }
            })
            ->where('status', 'active')
            ->exists();

        if (! $hasActiveBooking) {
            $lead->update(['booking_status' => 'NotBooked']);
        }
    }

    public function convertLeadToClient(Lead $lead): Client
    {
        $client = $this->findOrCreateClient($lead);

        $lead->update([
            'converted_to_client' => true,
            'client_id' => $client->id,
            'lead_status' => 'Hot',
        ]);

        return $client;
    }

    private function findLeadByEmail(?string $email): ?Lead
    {
        if (! $email) {
            return null;
        }

        return Lead::query()
            ->whereRaw('lower(email) = ?', [strtolower($email)])
            ->latest()
            ->first();
    }

    private function findLeadByBookingToken(?string $bookingToken): ?Lead
    {
        if (! $bookingToken) {
            return null;
        }

        return Lead::query()
            ->where('booking_token', $bookingToken)
            ->latest()
            ->first();
    }

    private function findOrCreateClient(Lead $lead): Client
    {
        $query = Client::query();

        if ($lead->email) {
            $client = (clone $query)->whereRaw('lower(email) = ?', [strtolower($lead->email)])->first();
            if ($client) {
                return $client;
            }
        }

        $client = Client::query()
            ->whereRaw('lower(company_name) = ?', [strtolower($lead->company)])
            ->first();

        if ($client) {
            return $client;
        }

        return Client::create([
            'company_name' => $lead->company,
            'primary_contact' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'industry' => $lead->industry,
        ]);
    }
}
