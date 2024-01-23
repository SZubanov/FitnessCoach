<form id="create" method="POST" enctype="multipart/form-data" role="form">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                    <div class="form-group col-md-4 p-0">
                        <label>Дата</label>
                        <input name="date" type="text" maxlength="255" class="form-control" datepicker
                               data-inputmask
                               value="{{ isset($userSize) ? $userSize->date ?? '' : old('date') }}"
                        >
                    </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Шея</label>
                    <div class="d-flex align-items-center">
                        <input name="neck" type="text" maxlength="255" class="form-control" data-float-inputmask
                               value="{{ isset($userSize) ? $userSize->neck ?? '' : old('neck') }}"
                        >
                        <span class="col-md-1 ml-2 text-muted">{{ $unit }}</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Грудь</label>
                    <div class="d-flex align-items-center">
                        <input name="chest" type="text" maxlength="255" class="form-control" data-float-inputmask
                               value="{{ isset($userSize) ? $userSize->chest ?? '' : old('chest') }}"
                        >
                        <span class="col-md-1 ml-2 text-muted">{{ $unit }}</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Талия</label>
                    <div class="d-flex align-items-center">
                        <input name="waist" type="text" maxlength="255" class="form-control" data-float-inputmask
                               value="{{ isset($userSize) ? $userSize->waist ?? '' : old('waist') }}"
                        >
                        <span class="col-md-1 ml-2 text-muted">{{ $unit }}</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Бицепс</label>
                    <div class="d-flex align-items-center">
                        <input name="biceps" type="text" maxlength="255" class="form-control" data-float-inputmask
                               value="{{ isset($userSize) ? $userSize->biceps ?? '' : old('biceps') }}"
                        >
                        <span class="col-md-1 ml-2 text-muted">{{ $unit }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Таз</label>
                    <div class="d-flex align-items-center">
                        <input name="pelvis" type="text" maxlength="255" class="form-control" data-float-inputmask
                               value="{{ isset($userSize) ? $userSize->pelvis ?? '' : old('pelvis') }}"
                        >
                        <span class="col-md-1 ml-2 text-muted">{{ $unit }}</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Бедро</label>
                    <div class="d-flex align-items-center">
                        <input name="thigh" type="text" maxlength="255" class="form-control" data-float-inputmask
                               value="{{ isset($userSize) ? $userSize->thigh ?? '' : old('thigh') }}"
                        >
                        <span class="col-md-1 ml-2 text-muted">{{ $unit }}</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Голень</label>
                    <div class="d-flex align-items-center">
                        <input name="tibia" type="text" maxlength="255" class="form-control" data-float-inputmask
                               value="{{ isset($userSize) ? $userSize->tibia ?? '' : old('tibia') }}"
                        >
                        <span class="col-md-1 ml-2 text-muted">{{ $unit }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-success modal-button-form"
                @if(isset($diary)) onclick="Main.updateRecord('{{ route('web.users.diary.store.steps', $diary->id) }}')"
                {{-- TODO add a route for update --}}
                @else onclick="Main.storeRecord('{{ route('web.users.diary.store.steps') }}')" @endif
        >Создать
        </button>
        <button type="button" class="btn btn-danger" onclick="Main.dissmissModal('#form')">Отмена</button>
    </div>
</form>
