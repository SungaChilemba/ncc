<?php

namespace Database\Factories;

use App\Models\Report;
use App\Support\ReportReference;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        $receivedAt = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'reference_number' => ReportReference::generate(),
            'access_pin_hash' => Hash::make('123456'),
            'status' => 'received',
            'priority' => fake()->randomElement(Report::PRIORITIES),
            'violation_type' => fake()->randomElement(Report::VIOLATION_TYPES),
            'incident_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'district' => fake()->randomElement([
                'Blantyre', 'Lilongwe', 'Mzimba', 'Zomba', 'Kasungu', 'Mangochi',
            ]),
            'village' => fake()->optional()->citySuffix(),
            'child_age' => fake()->numberBetween(0, 17),
            'child_gender' => fake()->randomElement(Report::GENDERS),
            'child_name' => fake()->optional()->name(),
            'description' => fake()->paragraph(),
            'reporter_name' => fake()->optional()->name(),
            'reporter_contact' => fake()->optional()->phoneNumber(),
            'is_anonymous' => false,
            'consent_given' => true,
            'received_at' => $receivedAt,
        ];
    }

    public function assignedTo($user): static
    {
        return $this->state(fn () => ['assigned_to' => $user->id]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => 'closed',
            'closed_at' => now(),
            'closure_outcome' => 'Resolved',
            'retention_delete_at' => now()->addYears(2),
        ]);
    }
}
