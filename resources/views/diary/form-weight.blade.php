<form id="create" method="POST" enctype="multipart/form-data" role="form">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Дата<sup class="text-danger">*</sup></label>
                    <input name="date" type="text" maxlength="255" class="form-control" required datepicker data-inputmask
                           value="{{ isset($diary) ? $diary->date ?? '' : old('kcal') }}"
                    >
                </div>
                <div class="form-group">
                    <label>Вес<sup class="text-danger">*</sup></label>
                    <input name="weight" type="text" maxlength="255" class="form-control" required
                           value="{{ isset($diary) ? $diary->weight ?? '' : old('weight') }}"
                    >
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-success modal-button-form"
                @if(isset($diary)) onclick="Main.updateRecord('{{ route('web.users.diary.store.weight', $diary->id) }}')" {{-- TODO add a route for update --}}
                @else onclick="Main.storeRecord('{{ route('web.users.diary.store.weight') }}')" @endif
        >Создать
        </button>
        <button type="button" class="btn btn-danger" onclick="Main.dissmissModal('#form')">Отмена</button>
    </div>
</form>
