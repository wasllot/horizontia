<main class="tb-main">
    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="tb-dhb-mainheading">
                <h4>Leads & Contactos</h4>
                <div class="tb-sortby">
                    <form class="tb-themeform tb-displistform">
                        <fieldset>
                            <div class="tb-themeform__wrap">
                                <div class="tb-actionselect">
                                    <a href="#" wire:click.prevent="switchTab('contacts')" class="tb-btn {{ $activeTab === 'contacts' ? '' : 'btn-outline' }}" style="{{ $activeTab !== 'contacts' ? 'background: transparent; color: #585858; border: 1px solid #ddd; font-weight: 500;' : '' }}">Mensajes de Contacto</a>
                                </div>
                                <div class="tb-actionselect">
                                    <a href="#" wire:click.prevent="switchTab('subscribers')" class="tb-btn {{ $activeTab === 'subscribers' ? '' : 'btn-outline' }}" style="{{ $activeTab !== 'subscribers' ? 'background: transparent; color: #585858; border: 1px solid #ddd; font-weight: 500;' : '' }}">Suscriptores Newsletter</a>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
            <div class="am-disputelist_wrap">
                <div class="am-disputelist am-custom-scrollbar-y">
                    @if($activeTab === 'contacts')
                        @if($contacts->isEmpty())
                            <x-no-record :image="asset('images/empty.png')" :title="'No hay mensajes de contacto'"/>
                        @else
                            <table class="tb-table @if(setting('_general.table_responsive') == 'yes') tb-table-responsive @endif">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Asunto</th>
                                        <th>Mensaje</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contacts as $contact)
                                        <tr>
                                            <td data-label="Nombre">
                                                <div class="tb-varification_userinfo">
                                                    <span>{{ $contact->name }}</span>
                                                </div>
                                            </td>
                                            <td data-label="Email"><span><a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></span></td>
                                            <td data-label="Asunto"><span><strong>{{ $contact->subject }}</strong></span></td>
                                            <td data-label="Mensaje">
                                                <span style="display:inline-block; max-width:250px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $contact->message }}">
                                                    {{ $contact->message }}
                                                </span>
                                            </td>
                                            <td data-label="Fecha"><span>{{ $contact->created_at->format('d/m/Y H:i') }}</span></td>
                                            <td data-label="Acciones">
                                                <ul class="tb-action-icon">
                                                    <li>
                                                        <a href="javascript:void(0);" wire:click="viewContact({{ $contact->id }})" class="am-custom-tooltip">
                                                            <span class="am-tooltip-text"><span>Ver Detalles</span></span>
                                                            <i class="icon-eye"></i>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" @click="$wire.dispatch('showConfirm', { id : {{ $contact->id }}, content: '¿Estás seguro de eliminar este mensaje?', action : 'delete-contact' })" class="am-custom-tooltip">
                                                            <span class="am-tooltip-text"><span>Eliminar</span></span>
                                                            <i class="icon-trash-2"></i>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            {{ $contacts->links('pagination.custom') }}
                        @endif
                    @else
                        @if($subscribers->isEmpty())
                            <x-no-record :image="asset('images/empty.png')" :title="'No hay suscriptores aún'"/>
                        @else
                            <table class="tb-table @if(setting('_general.table_responsive') == 'yes') tb-table-responsive @endif">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Email Suscrito</th>
                                        <th>Estado</th>
                                        <th>Fecha de Suscripción</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subscribers as $subscriber)
                                        <tr>
                                            <td data-label="ID"><span>{{ $subscriber->id }}</span></td>
                                            <td data-label="Email"><span><a href="mailto:{{ $subscriber->email }}">{{ $subscriber->email }}</a></span></td>
                                            <td data-label="Estado">
                                                <div class="am-status-tag">
                                                    @if($subscriber->is_active)
                                                        <em class="tk-project-tag tk-hourly-tag">Activo</em>
                                                    @else
                                                        <em class="tk-project-tag tk-fixed-tag">Inactivo</em>
                                                    @endif
                                                </div>
                                            </td>
                                            <td data-label="Fecha"><span>{{ $subscriber->created_at->format('d/m/Y H:i') }}</span></td>
                                            <td data-label="Acciones">
                                                <ul class="tb-action-icon">
                                                    <li>
                                                        <a href="javascript:void(0);" @click="$wire.dispatch('showConfirm', { id : {{ $subscriber->id }}, content: '¿Estás seguro de eliminar este suscriptor?', action : 'delete-subscriber' })" class="am-custom-tooltip">
                                                            <span class="am-tooltip-text"><span>Eliminar</span></span>
                                                            <i class="icon-trash-2"></i>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            {{ $subscribers->links('pagination.custom') }}
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- View Contact Modal -->
    <div wire:ignore.self class="modal fade tb-addonpopup" id="view-contact-modal" aria-labelledby="view_contact_label" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg tb-modaldialog" role="document">
            <div class="modal-content">
                <div class="tb-popuptitle">
                    <h5 id="view_contact_label">Detalles del Mensaje</h5>
                    <a href="javascript:void(0);" class="close"><i class="icon-x" data-bs-dismiss="modal"></i></a>
                </div>
                <div class="modal-body">
                    @if($viewingContact)
                        <form class="tb-themeform">
                            <fieldset>
                                <div class="form-group-wrap">
                                    <div class="form-group form-group-half">
                                        <label class="tb-label">Nombre del Remitente</label>
                                        <div class="tb-inputicon">
                                            <i class="icon-user"></i>
                                            <input type="text" class="form-control" value="{{ $viewingContact->name }}" readonly>
                                        </div>
                                    </div>
                                    <div class="form-group form-group-half">
                                        <label class="tb-label">Correo Electrónico</label>
                                        <div class="tb-inputicon">
                                            <i class="icon-mail"></i>
                                            <input type="text" class="form-control" value="{{ $viewingContact->email }}" readonly>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="tb-label">Asunto</label>
                                        <input type="text" class="form-control" value="{{ $viewingContact->subject }}" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label class="tb-label">Mensaje</label>
                                        <textarea class="form-control" style="min-height: 180px; resize: none; background-color: #f9f9f9; padding: 15px;" readonly>{!! strip_tags($viewingContact->message) !!}</textarea>
                                    </div>
                                </div>
                            </fieldset>
                        </form>
                        <div class="tb-dbbox__footer" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                            <span class="text-muted" style="font-size: 13px;">
                                <i class="icon-clock"></i> Recibido el {{ $viewingContact->created_at->format('d \d\e F, Y \a \l\a\s H:i') }}
                            </span>
                            <div class="float-end">
                                <a href="mailto:{{ $viewingContact->email }}" class="tb-btn">Responder por Email <i class="icon-external-link"></i></a>
                            </div>
                            <div style="clear: both;"></div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</main>
