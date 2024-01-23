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
                    <div class="d-flex align-items-center">
                        <input name="weight" type="text" maxlength="255" class="form-control" required data-float-inputmask
                               value="{{ isset($diary) ? $diary->weight ?? '' : old('weight') }}">
                        <select name="unit" class="form-control ml-2 col-md-2" required>
                            <option value="g" selected>g</option>
                            <option value="lb">lb</option>
                            <option value="kg">kg</option>
                            <option value="oz">oz</option>
                        </select>
                    </div>
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
