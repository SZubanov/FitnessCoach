<form id="create" method="POST" enctype="multipart/form-data" role="form">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Имя<sup class="text-danger">*</sup></label>
                    <input name="name" type="text" maxlength="255" class="form-control" required
                           value="{{ isset($user) ? $user->name ?? '' : old('name') }}"
                    >
                </div>
                <div class="form-group">
                    <label>Email<sup class="text-danger">*</sup></label>
                    <input name="email" type="email" maxlength="255" class="form-control" required
                           value="{{ isset($user) ? $user->email ?? '' : old('email') }}"
                    >
                </div>
                <div class="form-group">
                    <label>Роль<sup class="text-danger">*</sup></label>
                    <select name="role" class="form-control" required>
                        @if($roles->isNotEmpty())
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}"
                                        @if((old('role') and old('role') == $role->id)
                                            || (isset($user) and $user->roles->first()?->id == $role->id))
                                        selected
                                    @endif
                                >
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Пароль @if($method == 'create')<sup class="text-danger">*</sup>@endif</label>
                    <input name="password" type="password" maxlength="255" class="form-control" @if($method == 'create') required @endif>
                </div>
                <div class="form-group">
                    <label>Повторите пароль@if($method == 'create')<sup class="text-danger">*</sup>@endif</label>
                    <input name="password_confirmation" type="password" maxlength="255" class="form-control" @if($method == 'create') required @endif>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-success modal-button-form"
                @if(isset($user)) onclick="Main.updateRecord('{{ route('web.admin.users.update', $user->id) }}')"
                @else onclick="Main.storeRecord('{{ route('web.admin.users.store') }}')" @endif
        >Создать
        </button>
        <button type="button" class="btn btn-danger" onclick="Main.dissmissModal('#form')">Отмена</button>
    </div>
</form>
