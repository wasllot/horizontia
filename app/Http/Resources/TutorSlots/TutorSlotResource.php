<?php

namespace App\Http\Resources\TutorSlots;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
class TutorSlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [

            'id'                                => $this->whenHas('id'),
            'date'                              => $this->whenHas('start_time', function ($start_time) {
                return parseToUserTz($start_time)->format(!empty(setting('_general.date_format')) ? setting('_general.date_format') : 'F j, Y');
            }),
            'start_time'                        => $this->when($this->start_time, function () {
                if(setting('_lernen.time_format') == '12'){
                    return $this->start_time->format('g:i a');
                }else{
                    return $this->start_time->format('H:i');
                }
            }),
            'end_time'                          => $this->when($this->end_time, function () {
                if(setting('_lernen.time_format') == '12'){
                    return $this->end_time->format('g:i a');
                }else{
                    return $this->end_time->format('H:i');
                }
            }),
            'formatted_time_range'              => $this->when($this->start_time && $this->end_time, function () {
                if(setting('_lernen.time_format') == '12'){
                    return $this->start_time->format('g:i a') . ' - ' . $this->end_time->format('g:i a');
                }else{
                    return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
                }
            }),
            'total_slots'                            => $this->whenHas('spaces'),
            'spaces_type'                       => $this->whenHas('spaces') == 1 ? 'one' : 'group',
            'duration'                          => $this->whenHas('duration'),
            'booked_slots'                      => $this->whenHas('total_booked'),
            'available_slots'                    => $this->whenHas('total_booked', function () {
                return $this->spaces - $this->total_booked;
            }),
            'session_fee'                       => $this->whenHas('session_fee', function ($session_fee) {
                return formatAmount($session_fee);
            }),
            'type'                              => $this->whenHas('type'),
            'bookings_count'                    => $this->whenHas('bookings_count'),
            'description'                       => $this->whenHas('description'),
            'image'                             => $this->when($this->subjectGroupSubjects ,
                 fn () => !empty($this->subjectGroupSubjects->image) ? url(Storage::url($this->subjectGroupSubjects->image)) : url(Storage::url('placeholder.png'))),
            'subject'                           => $this->when(
                $this->subjectGroupSubjects && $this->subjectGroupSubjects->subject,
                fn () => $this->subjectGroupSubjects->subject
            ),
            'group'                             => $this->when(
                $this->subjectGroupSubjects && $this->subjectGroupSubjects->userSubjectGroup && $this->subjectGroupSubjects->userSubjectGroup->group,
                fn () => $this->subjectGroupSubjects->userSubjectGroup->group
            ),

        ];
        
    }
}
