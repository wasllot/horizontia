<main class="tb-main am-dispute-system am-user-system">
    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="tb-dhb-mainheading">
                <h4>{{ __('general.all_users') }} <span class="hz-count">{{ $users->total() }}</span></h4>
                <a href="javascript:void(0)" id="add_user_click" class="hz-btn hz-btn--sm add-new" data-bs-toggle="modal" data-bs-target="#tb-add-user"><i class="icon-plus" aria-hidden="true"></i> {{ __('general.add_new_user') }}</a>
            </div>
            <form class="hz-toolbar" onsubmit="return false">
                <div class="hz-toolbar__search">
                    <i class="icon-search" aria-hidden="true"></i>
                    <input type="search" class="form-control" wire:model.live.debounce.500ms="search" autocomplete="off" placeholder="{{ __('general.search') }}" aria-label="{{ __('general.search') }}">
                </div>
                <div class="hz-toolbar__filter" wire:ignore>
                    <select data-componentid="@this" class="am-select2 form-control" data-searchable="false" data-live="true" id="roles" data-wiremodel="roles" aria-label="{{ __('admin/general.role') }}">
                        <option value="" {{ $roles == '' ? 'selected' : '' }}>{{ __('Todos los roles') }}</option>
                        <option value="student" {{ $roles == 'student' || $role == 'student' ? 'selected' : '' }}>{{ $student_name }}</option>
                        <option value="tutor" {{ $roles == 'tutor' || $role == 'tutor' ? 'selected' : '' }}>{{ $tutor_name }}</option>
                    </select>
                </div>
                <div class="hz-toolbar__filter" wire:ignore>
                    <select data-componentid="@this" class="am-select2 form-control" data-searchable="false" data-live="true" id="filter_user" data-wiremodel="filterUser" aria-label="{{ __('general.status') }}">
                        <option value="" {{ $filterUser == '' ? 'selected' : '' }}>{{ __('Todos los estados') }}</option>
                        <option value="active" {{ $filterUser == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                        <option value="inactive" {{ $filterUser == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                    </select>
                </div>
                <div class="hz-toolbar__filter" wire:ignore>
                    <select data-componentid="@this" class="am-select2 form-control" data-searchable="false" data-live="true" id="verification" data-wiremodel="verification" aria-label="{{ __('general.email_verification') }}">
                        <option value="" {{ $verification == '' ? 'selected' : '' }}>{{ __('Toda verificación') }}</option>
                        <option value="verified" {{ $verification == 'verified' ? 'selected' : '' }}>{{ __('Verified users') }}</option>
                        <option value="unverified" {{ $verification == 'non_verified' ? 'selected' : '' }}>{{ __('Non verified users') }}</option>
                    </select>
                </div>
                <div class="hz-toolbar__filter" wire:ignore>
                    <select data-componentid="@this" class="am-select2 form-control" data-searchable="false" data-live="true" id="sort_by" data-wiremodel="sortby" aria-label="{{ __('Ordenar') }}">
                        <option value="desc" {{ $sortby == 'desc' ? 'selected' : '' }}>{{ __('Más recientes') }}</option>
                        <option value="asc" {{ $sortby == 'asc' ? 'selected' : '' }}>{{ __('Más antiguos') }}</option>
                    </select>
                </div>
            </form>
            <div class="am-disputelist_wrap">
                <div class="am-disputelist am-custom-scrollbar-y">
                    @if( !$users->isEmpty() )
                    <table class="tb-table @if(setting('_general.table_responsive') == 'yes') tb-table-responsive @endif tb-users-table">
                        <thead>
                            <tr>
                                <th>{{ __('general.user') }}</th>
                                <th>{{ __('admin/general.role') }}</th>
                                <th>{{ __('general.created_date') }}</th>
                                <th>{{ __('general.email_verification') }}</th>
                                <th>{{ __('general.identity_verification') }}</th>
                                <th>{{ __('general.status') }}</th>
                                <th>{{ __('general.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $single)
                                @php
                                    $roleName   = $single->roles()->first()->name;
                                    $verifiedAt = $single->email_verified_at ? 'verified' : 'non_verified';
                                    $emailMsg   = $single->email_verified_at ? __('general.reject_account') : __('general.approve_this_account');
                                    $statusMsg  = $single->status == 'active' ? __('general.disable_user') : __('general.enable_user');
                                @endphp
                                <tr wire:key="user-{{ $single->id }}">
                                    <td data-label="{{ __('general.user') }}">
                                        <x-panel.user-cell :name="$single->profile->full_name" :email="$single->email" :image="$single->profile->image" :url="$roleName == 'tutor' ? route('tutor-detail', ['slug' => $single->profile->slug]) : null" />
                                    </td>
                                    <td data-label="{{ __('admin/general.role') }}">{{ __(ucfirst($roleName)) }}</td>
                                    <td data-label="{{ __('general.created_date') }}">{{ $single->created_at->translatedFormat('d M Y') }}</td>
                                    <td data-label="{{ __('general.email_verification') }}">
                                        <button type="button" class="hz-chipbtn" title="{{ $emailMsg }}"
                                            @click="$wire.dispatch('showConfirm', { id : {{ $single->id }}, content: '{{ $emailMsg }}', type: '{{ $verifiedAt }}', action : 'verified-at-template' })">
                                            <x-panel.badge :tone="$single->email_verified_at ? 'success' : 'warning'">{{ $single->email_verified_at ? __('general.verified') : __('general.non_verified') }}</x-panel.badge>
                                        </button>
                                    </td>
                                    <td data-label="{{ __('general.identity_verification') }}">
                                        <x-panel.badge :tone="!empty($single->profile->verified_at) ? 'success' : 'neutral'">{{ !empty($single->profile->verified_at) ? __('general.verified') : __('general.non_verified') }}</x-panel.badge>
                                    </td>
                                    <td data-label="{{ __('general.status') }}">
                                        <button type="button" class="hz-chipbtn" title="{{ $statusMsg }}"
                                            @click="$wire.dispatch('showConfirm', { id : {{ $single->id }}, content: `{{ $statusMsg }}`, type: '{{ $single->status }}', action : 'update-status' })">
                                            <x-panel.badge :tone="$single->status == 'active' ? 'success' : 'warning'">{{ __(ucfirst($single->status)) }}</x-panel.badge>
                                        </button>
                                    </td>
                                    <td data-label="{{ __('general.actions') }}">
                                        <div class="hz-acts">
                                            <x-panel.action icon="icon-edit-3" :label="__('general.edit_user')" @click="$wire.dispatch('edit-user', { id : {{ $single->id }} })" />
                                            @if (!empty($single->email_verified_at))
                                                <x-panel.action icon="icon-eye" :label="__('admin/general.impersonate_user')" @click="$wire.dispatch('showConfirm', { id : {{ $single->id }}, action : 'impersonate-user' })" />
                                            @endif
                                            <x-panel.action icon="icon-trash-2" tone="danger" :label="__('general.remove_user')" @click="$wire.dispatch('showConfirm', { id : {{ $single->id }}, content: '{{ __('general.delete_user') }}', action : 'delete-user' })" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $users->links('pagination.custom') }}
                    @else
                        <x-no-record :image="asset('images/empty.png')"  :title="__('general.no_record_title')"/>
                    @endif
                </div>
            </div>
        </div>
        <div wire:ignore.self class="modal fade tb-addonpopup" id="tb-add-user" aria-labelledby="tb_user_info_label"
            role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg tb-modaldialog" role="document">
                <div class="modal-content">
                    <div class="tb-popuptitle">
                        <h5 id="tb_user_info_label">{{__('general.user_information')}}</h5>
                        <a href="javascript:void(0);" class="close"><i class="icon-x" data-bs-dismiss="modal"></i></a>
                    </div>
                    <div class="modal-body">
                        <form class="tb-themeform" wire:submit.prevent="addUser" id="add_user_form">
                            <fieldset>
                                <div class="form-group-wrap">
                                    <div class="form-group">
                                        <label class="tb-label">{{__('general.first_name')}}</label>
                                        <input type="text"
                                            class="form-control @error('form.first_name') tk-invalid @enderror"
                                            wire:model="form.first_name" placeholder="{{__('general.name_placeholder')}}">
                                        @error('form.first_name')
                                        <div class="tk-errormsg">
                                            <span>{{$message}}</span>
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="tb-label">{{__('general.last_name')}}</label>
                                        <input type="text"
                                            class="form-control @error('form.last_name') tk-invalid @enderror"
                                            wire:model="form.last_name"
                                            placeholder="{{__('general.lastname_placeholder')}}">
                                        @error('form.last_name')
                                        <div class="tk-errormsg">
                                            <span>{{$message}}</span>
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="tb-label">{{__('general.email')}}</label>
                                        <input type="text" class="form-control @error('form.email') tk-invalid @enderror"
                                            wire:model="form.email" placeholder="{{__('general.email_placeholder')}}">
                                        @error('form.email')
                                        <div class="tk-errormsg">
                                            <span>{{$message}}</span>
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="tb-label">{{__('User role')}}</label>
                                        <div class="tk-error @error('form.userRole') tk-invalid @enderror">
                                            <div class="tb-select" wire:ignore>
                                                <select data-componentid="@this" class="am-select2 form-control"
                                                    data-searchable="false" data-parent="#tb-add-user" data-live='true'
                                                    id="user_role" data-wiremodel="form.userRole">
                                                    <option value="tutor">{{$tutor_name}}</option>
                                                    <option value="student">{{$student_name}}</option>
                                                </select>
                                            </div>
                                        </div>
                                        @error('form.userRole')
                                        <div class="tk-errormsg">
                                            <span>{{$message}}</span>
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="tb-label">{{__('general.password')}}</label>
                                        <input type="password" wire:model="form.password"
                                            class="form-control @error('form.password') tk-invalid @enderror"
                                            placeholder="{{__('general.password_placeholder')}}">
                                        @error('form.password')
                                        <div class="tk-errormsg">
                                            <span>{{$message}}</span>
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="tb-label">{{__('general.confirm_password')}}</label>
                                        <input type="password" wire:model="form.confirm_password"
                                            class="form-control @error('form.confirm_password') tk-invalid @enderror"
                                            placeholder="{{__('general.password_placeholder')}}">
                                        @error('form.confirm_password')
                                        <div class="tk-errormsg">
                                            <span>{{$message}}</span>
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="form-group tb-formbtn">
                                        <button class="tb-btn" type="submit" wire:target="addUser"
                                            wire:loading.class="am-btn_disable">{{__('general.save_user')}}</button>
                                    </div>
                                </div>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div wire:ignore.self class="modal fade tb-addonpopup" id="tb-edit-user" aria-labelledby="tb_user_edit_label"
            role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg tb-modaldialog" role="document">
                <div class="modal-content">
                    <div class="tb-popuptitle">
                        <h5 id="tb_user_edit_label">{{__('general.edit_user')}}</h5>
                        <a href="javascript:void(0);" class="close"><i class="icon-x" data-bs-dismiss="modal"></i></a>
                    </div>
                    <div class="modal-body">
                        <form class="tb-themeform" wire:submit.prevent="updateUser" id="edit_user_form">
                            <fieldset>
                                <div class="form-group-wrap">
                                    <div class="form-group">
                                        <label class="tb-label">{{__('general.first_name')}}</label>
                                        <input type="text"
                                            class="form-control @error('form.first_name') tk-invalid @enderror"
                                            wire:model="form.first_name" placeholder="{{__('general.name_placeholder')}}">
                                        @error('form.first_name')
                                        <div class="tk-errormsg">
                                            <span>{{$message}}</span>
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="tb-label">{{__('general.last_name')}}</label>
                                        <input type="text"
                                            class="form-control @error('form.last_name') tk-invalid @enderror"
                                            wire:model="form.last_name"
                                            placeholder="{{__('general.lastname_placeholder')}}">
                                        @error('form.last_name')
                                        <div class="tk-errormsg">
                                            <span>{{$message}}</span>
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="tb-label">{{__('general.email')}}</label>
                                        <input type="text" class="form-control @error('form.email') tk-invalid @enderror"
                                            wire:model="form.email" placeholder="{{__('general.email_placeholder')}}">
                                        @error('form.email')
                                        <div class="tk-errormsg">
                                            <span>{{$message}}</span>
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="tb-label">{{__('User role')}}</label>
                                        <div class="tk-error @error('form.userRole') tk-invalid @enderror">
                                            <div class="tb-select" wire:ignore>
                                                <select data-componentid="@this" class="am-select2 form-control"
                                                    data-searchable="false" data-parent="#tb-edit-user" data-live='true'
                                                    id="edit_user_role" data-wiremodel="form.userRole">
                                                    <option value="tutor">{{$tutor_name}}</option>
                                                    <option value="student">{{$student_name}}</option>
                                                </select>
                                            </div>
                                        </div>
                                        @error('form.userRole')
                                        <div class="tk-errormsg">
                                            <span>{{$message}}</span>
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="tb-label">{{__('general.new_password')}}</label>
                                        <input type="password" wire:model="form.password"
                                            class="form-control @error('form.password') tk-invalid @enderror"
                                            placeholder="{{__('general.password_placeholder')}}">
                                        @error('form.password')
                                        <div class="tk-errormsg">
                                            <span>{{$message}}</span>
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="tb-label">{{__('general.confirm_password')}}</label>
                                        <input type="password" wire:model="form.confirm_password"
                                            class="form-control @error('form.confirm_password') tk-invalid @enderror"
                                            placeholder="{{__('general.password_placeholder')}}">
                                        @error('form.confirm_password')
                                        <div class="tk-errormsg">
                                            <span>{{$message}}</span>
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="form-group tb-formbtn">
                                        <button class="tb-btn" type="submit" wire:target="updateUser"
                                            wire:loading.class="am-btn_disable">{{__('general.update_user')}}</button>
                                    </div>
                                </div>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
