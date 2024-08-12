<?php

namespace App\Http\Resources;

use App\Models\Admin;
use App\Models\Employee;
use Illuminate\Http\Resources\Json\JsonResource;

class SalonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $isSalonDetails = $request->routeIs('salon_details');

        return
        [
            'id' => $this->id,
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            $this->mergeWhen(!$isSalonDetails, [
                'logo_image' => $this->logo_image,
                'description' => $this->description,
            ]),

            $this->mergeWhen($isSalonDetails, [
                'logo_image' => $this->logo_image,
                'description' => $this->description,
                'status' => $this->status,
                'admin' => $this->admin ? $this->admin->user_name : "No Admin Available",
            ]),
            $this->mergeWhen($isSalonDetails && $this->employees && $this->employees->isNotEmpty(), [
                'employees' => EmployeeResource::collection($this->whenLoaded('employees')),

            ]),

            $this->mergeWhen($isSalonDetails && $this->products&& $this->products->isNotEmpty(), [
                'products' => ProductResource::collection($this->whenLoaded('products')),

            ]),
            $this->mergeWhen($isSalonDetails && $this->services && $this->services->isNotEmpty(), [
                'services' => ServiceResource::collection($this->whenLoaded('services')),

            ]),
            
        ];        
    }
}
