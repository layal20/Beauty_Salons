<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $isServiceDetails = $request->routeIs('service_details');
        $isServiceSalon = $request->routeIs('salon_details');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'status' => $this->status,
            $this->mergeWhen($isServiceDetails && $this->admins && $this->admins->isNotEmpty(), [
                'admin' => AdminResource::collection($this->whenLoaded('admin')),
            ]),
            $this->mergeWhen($isServiceDetails && $this->employee, [
                'employee' => $this->employee->name ?? 'No Employee available',
            ]),
            $this->mergeWhen($isServiceDetails && $this->salons && $this->salons->isNotEmpty(), [
                'salons' => SalonResource::collection($this->whenLoaded('salons')),
            ]),
            $this->mergeWhen($isServiceSalon && $this->employee, [
                'employee' => $this->employee->name ?? 'No employee available',
            ]),
            


        ];
    }
}
