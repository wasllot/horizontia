<?php
namespace App\Livewire\Pages\Common\ProfileSettings;

use App\Http\Requests\Common\AccountSetting\AccountSettingStoreRequest;
use Illuminate\Support\Facades\Auth;
use App\Services\UserService;
use Livewire\Attributes\Layout;
use App\Services\GoogleCalender;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class AccountSettings extends Component
{
    public  $reminder               = 30;
    public  $timezone;
    private $userService            = null;
    public  string $password        = '';
    public  $getAccountSetting      = null;
    public  string $confirm         = '';
    private $googleCalenderService  = null;
    public $activeRoute             = false;

    #[Layout('layouts.app')]
    public function render()
    {
        $this->getAccountSetting   = $this->userService->getAccountSetting();
        $this->timezone            = $this->getAccountSetting['timezone'][0] ?? 'UTC';
        if(!empty($this->getAccountSetting['google_calendar_info'])){
            $this->reminder = $this->getAccountSetting['google_calendar_info']['minutes'];
        }
        return view('livewire.pages.common.profile-settings.account-settings');
    }

    public function boot()
    {
        $this->googleCalenderService  = new GoogleCalender(Auth::user());
        $this->userService            = new UserService(Auth::user());
    }

    public function mount()
    {
        $this->activeRoute = Route::currentRouteName();
        $this->dispatch('initSelect2', target: '.am-select2' );
    }

    public function updatePassword()
    {
        $request    = new AccountSettingStoreRequest();
        $rules      = $request->rules();
        $data       = $this->validate($rules);
        $response   = isDemoSite();
        if( $response ){
            $this->dispatch('showAlertMessage', type: 'error', title:  __('general.demosite_res_title') , message: __('general.demosite_res_txt'));
            return;
        }
        if($data){
            $this->userService->setUserPassword($this->password);
            $this->reset('password', 'confirm');
        }

        $this->dispatch('showAlertMessage', type: 'success', title: __('passwords.success') , message: __('passwords.password_changed_successfully'));
    }

    public function saveTimezone()
    {

        $request    = new AccountSettingStoreRequest();
        $rules      = $request->rules(true);
        $data       = $this->validate($rules);
        $response   = isDemoSite();
        if( $response ){
            $this->dispatch('showAlertMessage', type: 'error', title:  __('general.demosite_res_title') , message: __('general.demosite_res_txt'));
            return;
        }
        if($data){
            $this->userService->setAccountSetting('timezone',[$this->timezone]);
            Cache::forget('userTimeZone_' . Auth::user()?->id);
            $this->reset('timezone');
        }
        $this->dispatch('showAlertMessage', type: 'success', title: __('passwords.success') , message: __('settings.save_time_zone_successfully'));
    }

    public function connectCalender()
    {
        $response = isDemoSite();
        if( $response ){
            $this->dispatch('showAlertMessage', type: 'error', title:  __('general.demosite_res_title') , message: __('general.demosite_res_txt'));
            return;
        }
        $authUrlResponse = $this->googleCalenderService->getAuthUrl();
        if($authUrlResponse['status'] == Response::HTTP_OK){
            $this->redirect($authUrlResponse['url']);
        } else {
            $this->dispatch('showAlertMessage', type: 'error', message: $authUrlResponse['message']);
        }
    }

    public function disconnectCalender()
    {
        $this->userService->setAccountSetting(['google_access_token','google_calendar_info']);
        $this->dispatch('showAlertMessage', type: 'success', title: __('passwords.success') , message: __('passwords.disconnect_calender'));
    }


    public function saveReminder()
    {
        $userPrimaryCalendar  = $this->googleCalenderService->updateCalendarNotificationSettings($this->reminder);
        if($userPrimaryCalendar['status'] == 200){
            $calendarData               = $this->getAccountSetting['google_calendar_info'];
            $calendarData['minutes']    = $this->reminder;
            $this->userService->setAccountSetting('google_calendar_info',  $calendarData);
            $this->dispatch('showAlertMessage', type: 'success', title: __('passwords.success') , message: __('passwords.update_calendar_notification'));
        } else {
            $this->dispatch('showAlertMessage', type: 'error', title: __('passwords.update_reminder') , message: $userPrimaryCalendar['message']);
        }
    }

}
