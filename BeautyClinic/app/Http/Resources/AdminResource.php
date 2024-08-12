<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $isAdminDetails = $request->routeIs('admin_details');
        return [
            'id' => $this->id,
            'name' => $this->user_name,
            $this->mergeWhen($isAdminDetails && $this->salon, [
                'salon' => $this->salon ? $this->salon->name  : 'No salon available',
            ]),


            $this->mergeWhen($isAdminDetails && $this->services && $this->services->isNotEmpty(), [
                'services' => ServiceResource::collection($this->whenLoaded('services')),

            ]),

            $this->mergeWhen($isAdminDetails && $this->products && $this->products->isNotEmpty(), [
                'products' => ProductResource::collection($this->whenLoaded('products'))

            ])

        ];
    }
}
