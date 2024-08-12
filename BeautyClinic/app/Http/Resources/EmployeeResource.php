<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $isemployeeDetails = $request->routeIs('employee_details');
        $isCustomer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $isAdmin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;;
        $isSuperAdmin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image,
            $this->mergeWhen($isAdmin || $isSuperAdmin, [
                'salary' => $this->salary,

            ]),

            $this->mergeWhen($isemployeeDetails && $this->admin, [
                'admin' => new AdminResource($this->whenLoaded('admin')),
            ]),

            $this->mergeWhen($isemployeeDetails && $this->service, [
                'service' => new ServiceResource($this->whenLoaded('service')),

            ]),
            $this->mergeWhen($isemployeeDetails && $this->salon,[
                'salon' => $this->salon ? $this->salon->name : 'No salon available',

            ])
        ];
    }
}
