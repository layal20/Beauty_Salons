<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentDetails;
use App\Models\Salon;
use App\Models\SalonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentsController extends Controller
{
    public function addAnAppointment(Request $request, $serviceId)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->can('book an appointment')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'date' => 'required|date_format:Y-m-d|after:tomorrow',
            'time' => 'required|date_format:H:i:s|after:10:00:00'
        ]);

        $service = SalonService::find($serviceId);
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }
        $salonId = $service->salon_id;
        $existingAppointment = AppointmentDetails::query()
            ->where('service_id', $service->id)
            ->whereHas('appointment', function ($query) use ($request) {
                $query->where('date', $request->date)
                    ->where('time', $request->time);
            })
            ->where('salon_id', $salonId)
            ->first();
        if ($existingAppointment) {
            return response()->json(['message' => 'This service is already booked for this time'], 400);
        }
        $existingServiceAppointment = AppointmentDetails::where('customer_id', $customer->id)
            ->where('service_id', $service->id)
            ->whereHas('appointment', function ($query) use ($request) {
                $query->where('date', $request->date)
                    ->where('time', $request->time);
            })
            ->first();

        if ($existingServiceAppointment) {
            return response()->json(['message' => 'You have already booked this service at this time'], 400);
        }

        $existingAppointmentAtSameTime = AppointmentDetails::where('customer_id', $customer->id)
            ->whereHas('appointment', function ($query) use ($request) {
                $query->where('date', $request->date)
                    ->where('time', $request->time);
            })
            ->first();

        if ($existingAppointmentAtSameTime) {
            return response()->json(['message' => 'You already have another service booked at this time'], 400);
        }

        $appointment = Appointment::create([
            'date' => $request->date,
            'time' => $request->time,
        ]);

        AppointmentDetails::create([
            'appointment_id' => $appointment->id,
            'customer_id' => $customer->id,
            'salon_id' => $salonId,
            'service_id' => $service->id,
        ]);

        return response()->json(['message' => 'Service booked successfully']);
    }


    public function cancelAppointment($appointmentId)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->can('cancel appointment')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $appointment = Appointment::query()->where('id', $appointmentId)->first();
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }
        $appointmentDetails = AppointmentDetails::query()->where('appointment_id', $appointmentId)->where('customer_id', $customer->id)->first();
        if (!$appointmentDetails) {
            return response()->json(['message' => 'you do not have the permission to delete this appointment'], 404);
        }
        $appointment->delete();
        $appointmentDetails->delete();
        return response()->json(['message' => 'Service cancelled successfully']);
    }

    public function viewAllAppointments()
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->can('view all appointments')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($super_admin) {
            $appointments = AppointmentDetails::with(['appointment', 'service'])->get();
            if ($appointments->isEmpty()) {
                return response()->json([
                    'message' => 'Appointment not found',
                ], 404);
            }
            $totalPrice = $appointments->sum(function ($appointment) {
                return $appointment->service ? $appointment->service->price : 0;
            });

            return response()->json(['appointments' => $appointments, 'totalPrice' => $totalPrice]);
        } elseif ($admin) {
            $appointments = AppointmentDetails::query()->whereHas('service', function ($query) use ($admin) {
                $query->whereHas('admins', function ($q) use ($admin) {
                    $q->where('admin_id', $admin->id);
                });
            })->with(['appointment', 'service'])->get();
            if ($appointments->isEmpty()) {
                return response()->json([
                    'message' => 'Appointment not found',
                ], 404);
            }
            $totalPrice = $appointments->sum(function ($appointment) {
                return $appointment->service ? $appointment->service->price : 0;
            });
            return response()->json(['appointments' => $appointments, 'totalPrice' => $totalPrice]);
        } elseif ($customer) {
            $appointments = AppointmentDetails::query()->where('customer_id', $customer->id)->with('appointment', 'service')->get();
            if ($appointments->isEmpty()) {
                return response()->json([
                    'message' => 'Appointment not found',
                ], 404);
            }

            $respons = $appointments->toArray();
            unset($respons['customer_id']);
            $totalPrice = $appointments->sum(function ($appointment) {
                return $appointment->service ? $appointment->service->price : 0;
            });
            return response()->json(['appointments' => $appointments, 'totalPrice' => $totalPrice]);
        }
    }

    public function viewAppointment($appointmentId)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->can('view appointment')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($super_admin) {
            $appointment = AppointmentDetails::with(['service' => function ($query) {
                $query->select('id', 'name', 'price');
            }, 'appointment.customer'])->where('id', $appointmentId)->first();
            if (!$appointment) {
                return response()->json([
                    'message' => 'Appointment not found',
                ], 404);
            }
            $respons = $appointment->toArray();
            $respons['customer'] = $appointment->appointment->customer ? [
                'id' => $appointment->appointment->customer->id,
                'name' => $appointment->appointment->customer->name,
                'phone_number' => $appointment->appointment->customer->phone_number,
            ] : null;
            $respons['salon'] = [
                'id' => $appointment->salon->id,
                'name' => $appointment->salon->name,
            ];
            return response()->json($respons);
        } elseif ($admin) {
            $appointment = AppointmentDetails::with(['service:id,name,price', 'appointment'])->whereHas('service', function ($query) use ($admin) {
                $query->whereHas('admins', function ($q) use ($admin) {
                    $q->where('admin_id', $admin->id);
                });
            })->where('id', $appointmentId)->first();
            if (!$appointment) {
                return response()->json([
                    'message' => 'Appointment not found',
                ], 404);
            }
            $respons = $appointment->toArray();
            $respons['customer'] = $appointment->customer ? [
                'id' => $appointment->customer->id,
                'name' => $appointment->customer->name,
                'phone_number' => $appointment->customer->phone_number,
            ] : null;
            $respons['salon'] = [
                'id' => $appointment->salon->id,
                'name' => $appointment->salon->name,
            ];
            return response()->json($respons);
        } elseif ($customer) {
            $appointment = AppointmentDetails::with(['service' => function ($query) {
                $query->select('id', 'name', 'price');
            }, 'appointment'])->where('id', $appointmentId)->where('customer_id', $customer->id)->first();
            if (!$appointment) {
                return response()->json([
                    'message' => 'Appointment not found',
                ], 404);
            }
            $respons = $appointment->toArray();
            $respons['salon'] = [
                'id' => $appointment->salon->id,
                'name' => $appointment->salon->name,
            ];
            unset($respons['customer_id']);

            return response()->json($respons);
        }
    }
    public function updateAppointmentDetails(Request $request, $appointmentId)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }

        if (!$user->can('update appointment details')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'date' => 'sometimes|date_format:Y-m-d|after:tomorrow',
            'time' => 'sometimes|date_format:H:i:s|after:10:00:00',
            'service_id' => 'sometimes|exists:salon_services,service_id'
        ]);

        $appointment = AppointmentDetails::where('appointment_id', $appointmentId)->where('customer_id', $customer->id)->where('service_id', $request->service_id)->first();
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }

        $service = SalonService::find($request->service_id);
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $salonId = $service->salon_id;

        $existingAppointment = AppointmentDetails::query()
            ->where('service_id', $service->id)
            ->whereHas('appointment', function ($query) use ($request) {
                $query->where('date', $request->date)
                    ->where('time', $request->time);
            })
            ->where('salon_id', $salonId)
            ->where('appointment_id', '!=', $appointmentId)
            ->first();
        if ($existingAppointment) {
            return response()->json(['message' => 'This service is already booked for this time'], 400);
        }
        $existingAppointmentAtSameTime = AppointmentDetails::where('customer_id', $customer->id)
            ->whereHas('appointment', function ($query) use ($request) {
                $query->where('date', $request->date)
                    ->where('time', $request->time);
            })->where('appointment_id', '!=', $appointmentId)
            ->first();

        if ($existingAppointmentAtSameTime) {
            return response()->json(['message' => 'You already have another service booked at this time'], 400);
        }

        $appointment->update([
            'salon_id' => $salonId,
            'service_id' => $service->id,
        ]);

        $appointment->appointment()->update([
            'date' => $request->date,
            'time' => $request->time
        ]);

        return response()->json(['message' => 'Appointment updated successfully']);
    }

    public function getCustomerAppointments($customerId)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->can('view all user appointments')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($super_admin) {
            $appointments = AppointmentDetails::with(['appointment', 'service'])->where('customer_id', $customerId)->get();
            if ($appointments->isEmpty()) {
                return response()->json([
                    'message' => 'Appointment not found',
                ], 404);
            }
            $totalPrice = $appointments->sum(function ($appointment) {
                return $appointment->service ? $appointment->service->price : 0;
            });

            return response()->json(['appointments' => $appointments, 'totalPrice' => $totalPrice]);
        } elseif ($admin) {
            $appointments = AppointmentDetails::query()->whereHas('service', function ($query) use ($admin) {
                $query->whereHas('admins', function ($q) use ($admin) {
                    $q->where('admin_id', $admin->id);
                });
            })->with(['appointment', 'service'])->where('customer_id', $customerId)->get();
            if ($appointments->isEmpty()) {
                return response()->json([
                    'message' => 'Appointment not found',
                ], 404);
            }
            $totalPrice = $appointments->sum(function ($appointment) {
                return $appointment->service ? $appointment->service->price : 0;
            });
            return response()->json(['appointments' => $appointments, 'totalPrice' => $totalPrice]);
        }
    }
}
