<?php

namespace Database\Factories;

use App\Models\ZohoSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class ZohoSubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ZohoSubscription::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            "display_name" => $this->faker->name,
            "salutation" => $this->faker->title,
            "first_name" => $this->faker->firstName,
            "last_name" => $this->faker->lastName,
            "email" => $this->faker->safeEmail,

            "company_name" => $this->faker->company,
            "phone" => $this->faker->phoneNumber,
            "mobile" => $this->faker->phoneNumber,
            "department" => $this->faker->companySuffix,
            "designation" => $this->faker->jobTitle,
            "website" => $this->faker->url,
            
            "payment_terms" => $this->faker->randomDigit(1, 12),
            "payment_terms_label" => "Due on receipt",
           
            
        ];
    }
}
