<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Assessment;
use App\Models\AssessmentPillarScore;
use App\Models\AssessmentQuestionResponse;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(JsonAssessmentBankSeeder::class);

        $faker = \Faker\Factory::create();

        // Create a default admin user
        User::firstOrCreate(
            ['email' => 'admin@rab.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Add 5 clients
        $clients = [];
        for ($i = 0; $i < 5; $i++) {
            $clients[] = Client::create([
                'company_name' => $faker->company,
                'primary_contact' => $faker->name,
                'email' => $faker->companyEmail,
                'industry' => $faker->randomElement(['Technology', 'Finance', 'Healthcare', 'Manufacturing', 'Retail']),
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
            ]);
        }

        // Add 10 assessments
        $assessments = [];
        $types = ['PHI', 'ITSM', 'PIR', 'SIR'];
        for ($i = 0; $i < 10; $i++) {
            $type = $faker->randomElement($types);
            $score = $faker->randomFloat(2, 1.5, 4.8);
            
            if ($score < 2.5) $rag = 'Red';
            elseif ($score < 3.8) $rag = 'Amber';
            else $rag = 'Green';

            $assessment = Assessment::create([
                'client_id' => $clients[array_rand($clients)]->id,
                'name' => 'Q' . rand(1, 4) . ' ' . $faker->year . ' ' . $type . ' Assessment',
                'type' => $type,
                'overall_score' => $score,
                'rag_status' => $rag,
                'status' => $faker->randomElement(['draft', 'in_progress', 'approved']),
                'bri' => $type === 'PHI' ? $faker->randomFloat(2, 1, 5) : null,
                'vri' => $type === 'PHI' ? $faker->randomFloat(2, 1, 5) : null,
                'ssi' => $type === 'ITSM' ? $faker->randomFloat(2, 1, 5) : null,
                'smi' => $type === 'ITSM' ? $faker->randomFloat(2, 1, 5) : null,
                'bau_readiness' => $type === 'ITSM' ? $faker->randomFloat(2, 1, 5) : null,
                'critical_flag' => $score < 2.5,
                'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
            ]);

            $assessments[] = $assessment;

            // Generate Pillar Scores
            $pillars = in_array($type, ['PHI', 'PIR']) 
                ? ['Leadership & Culture', 'Process Management', 'Technology & Tools', 'Metrics & Reporting', 'Governance']
                : ['Service Desk', 'Incident Management', 'Problem Management', 'Change Management', 'Asset Management'];

            foreach ($pillars as $pillarName) {
                $pScore = max(1, min(5, $score + $faker->randomFloat(2, -0.8, 0.8)));
                if ($pScore < 2.5) $pRag = 'Red';
                elseif ($pScore < 3.8) $pRag = 'Amber';
                else $pRag = 'Green';

                AssessmentPillarScore::create([
                    'assessment_id' => $assessment->id,
                    'name' => $pillarName,
                    'score' => $pScore,
                    'rag_status' => $pRag,
                    'critical_flag' => $pScore < 2.0,
                ]);
            }
        }

        // Add 20 Leads
        for ($i = 0; $i < 20; $i++) {
            $score = $faker->randomFloat(2, 1, 5);
            if ($score < 2.5) $rag = 'Red';
            elseif ($score < 3.8) $rag = 'Amber';
            else $rag = 'Green';

            $priority = ($score < 3.0) ? 'High' : 'Normal';
            $isConverted = $faker->boolean(30);

            Lead::create([
                'name' => $faker->name,
                'company' => $faker->company,
                'type' => $faker->randomElement($types),
                'overall_score' => $score,
                'rag_status' => $rag,
                'priority' => $priority,
                'converted_to_client' => $isConverted,
                'client_id' => $isConverted ? $clients[array_rand($clients)]->id : null,
                'created_at' => $faker->dateTimeBetween('-3 months', 'now'),
            ]);
        }

        // Add detailed question responses for 2 full assessments
        $sampled = array_slice($assessments, 0, 2);
        foreach ($sampled as $assessment) {
            foreach ($assessment->pillarScores as $pillar) {
                // 3 questions per pillar
                for ($q = 1; $q <= 3; $q++) {
                    AssessmentQuestionResponse::create([
                        'assessment_id' => $assessment->id,
                        'pillar_name' => $pillar->name,
                        'question' => "Sample question {$q} for {$pillar->name}?",
                        'score' => rand(1, 5),
                        'evidence_note' => $faker->paragraph(2),
                        'confidence' => $faker->randomElement(['High', 'Med', 'Low']),
                    ]);
                }
            }
            
            // Generate some AI draft json
            $assessment->update([
                'ai_draft_json' => [
                    'executive_summary' => 'This is an AI generated draft executive summary.',
                    'key_findings' => [
                        'Critical gap in ' . $assessment->pillarScores()->first()->name,
                        'Strong performance in governance structure'
                    ],
                    'recommendations' => [
                        'Implement tool integration',
                        'Revise tier 1 support scripts'
                    ]
                ]
            ]);
        }

        // Add 10 mock bookings
        for ($i = 0; $i < 10; $i++) {
            \App\Models\Booking::create([
                'calendly_event_uuid' => $faker->uuid,
                'calendly_invitee_uuid' => $faker->uuid,
                'client_name' => $faker->name,
                'client_email' => $faker->safeEmail,
                'starts_at' => $faker->dateTimeBetween('now', '+2 weeks'),
                'ends_at' => $faker->dateTimeBetween('+2 weeks', '+3 weeks'),
                'status' => $faker->randomElement(['active', 'cancelled']),
                'event_type_name' => $faker->randomElement(['Rapid Consulting Call', 'Programme Health Check Deep Dive']),
                'join_url' => 'https://teams.microsoft.com/l/meetup-join/mock',
            ]);
        }
    }
}
