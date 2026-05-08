<?php

namespace Amentotech\LaraGuppy\Services;

use Amentotech\LaraGuppy\ConfigurationManager;
use Amentotech\LaraGuppy\Models\ChatAction;
use Amentotech\LaraGuppy\Models\Thread;
use Illuminate\Contracts\Pagination\Paginator;

class ChatService
{
    public function getContacts(): Paginator|null
    {
        $search  = request()->get('search');

        $userClass = (string)config('auth.providers.users.model');
        $with = ['chatActions','chatProfile'];
        if(!empty(config('laraguppy.userinfo_relation'))){
            array_push($with, config('laraguppy.userinfo_relation'));
        }
        return (new $userClass())::when(!empty(config('laraguppy.exclude_roles')), function($roles) {
            $roles->whereHas('roles', fn ($query) => $query->whereNotIn('name', config('laraguppy.exclude_roles') ));
        })->where('id', '<>', auth()->user()->id)
            ->when($search, function ($query) use ($search) {
                if(!empty(config('laraguppy.userinfo_relation'))){
                    $query->withWhereHas(config('laraguppy.userinfo_relation'), function ($query) use ($search) {
                        $query->where(function($where) use ($search) {
                            $where->where(config('laraguppy.user_first_name_column'), 'LIKE', "%{$search}%");
                            if(!empty(config('laraguppy.user_last_name_column'))){
                                $where->orWhere(config('laraguppy.user_last_name_column'), 'LIKE', "%{$search}%");
                            }  
                        });  
                    });
                } else {
                    $query->where(function($where) use ($search) {
                        $where->where(config('laraguppy.user_first_name_column'), 'LIKE', "%{$search}%");
                        if(!empty(config('laraguppy.user_last_name_column'))){
                            $where->orWhere(config('laraguppy.user_last_name_column'), 'LIKE', "%{$search}%");
                        }  
                    });  
                }
            })
            ->whereDoesntHave('friendsFrom', fn($friend) => $friend->whereFriendStatus(ConfigurationManager::ACTIVE_STATUS)->where('user_id', auth()->user()->id))
            ->whereDoesntHave('friendsTo',   fn($friend) => $friend->whereFriendStatus(ConfigurationManager::ACTIVE_STATUS)->where('friend_id', auth()->user()->id))
            ->whereDoesntHave('invitedFriendsTo', fn($invitedTo) => $invitedTo->where('user_id', auth()->user()->id))
            ->with(
                array_merge(
                [
                    'invitedFriendsFrom' => fn($invitedFrom) => $invitedFrom->where('user_id', auth()->user()->id),
                    'invitedFriendsTo'   => fn($invitedTo) => $invitedTo->where('user_id', auth()->user()->id),
                ],
                $with)
            )
            ->paginate(config('laraguppy.per_page_records'));

    }

    function chatActionRecord($userId, $action){
        $clearChat = ChatAction::where('user_id', $userId)
                    ->where('action', $action)->first();
            return !empty($clearChat) ? $clearChat->created_at : null;
    }

    public function clearChat($userId, $threadId){
        return ChatAction::create([
            'user_id'   => $userId,
            'actionable_id' => $threadId,
            'actionable_type' => Thread::class,
            'action'          => ConfigurationManager::CLEAR_CHAT_ACTION,
        ]);
    }

    public function muteChatNotification($userId, $threadId){
        return ChatAction::firstOrCreate([
            'user_id'   => $userId,
            'actionable_id' => $threadId,
            'actionable_type' => Thread::class,
            'action'          => ConfigurationManager::NOTIFICATION_MUTE,
        ]);
    }

    public function muteAccountNotification(){
        return ChatAction::firstOrCreate([
            'user_id'   => auth()->user()->id,
            'actionable_id' => auth()->user()->id,
            'actionable_type' => (string)config('auth.providers.users.model'),
            'action'          => ConfigurationManager::NOTIFICATION_MUTE,
        ]);
    }

    public function unmuteChatNotification($userId, $threadId){
        $muteAction = ChatAction::whereUserId($userId)->where('actionable_id', $threadId)->where('actionable_type',Thread::class)->whereAction(ConfigurationManager::NOTIFICATION_MUTE);
        if ($muteAction) {
            $muteAction->delete();
        }
    }

    public function unmuteAccountNotification(){
        $muteAction = ChatAction::whereUserId(auth()->user()->id)->where('actionable_id', auth()->user()->id)->where('actionable_type',config('auth.providers.users.model'))->whereAction(ConfigurationManager::NOTIFICATION_MUTE);
        if ($muteAction) {
            $muteAction->delete();
        }
    }
}
