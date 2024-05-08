<div class="modal fade" id="modal-messages" tabindex="-1" role="dialog" aria-labelledby="modal-messages-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><span id="modal-messages-title"> </span></h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="mMIdLocation"><b>Ubicación:</b></label>
                            <select name="mMIdLocation" id="mMIdLocation" class="form-control" disabled>
                                <option value="1">Tlaquiltenango</option>
                                <option value="2">Zacatepec</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="mMContactType"><b>Tipo:</b></label>
                            <select name="mMContactType" id="mMContactType" class="form-control" disabled>
                                <option value="1">SMS</option>
                                <option value="2">WhatsApp</option>
                                <option value="3">Casa</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="mMEstatus"><b>Estatus del Paquete:</b></label>
                            <select name="mMEstatus" id="mMEstatus" class="form-control" disabled>
                                    <option value="1">Nuevo</option>
                            </select>
                        </div>
                    </div>

                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="mMMessage"><b>Mensaje:</b></label>
                            <textarea class="form-control" id="mMMessage" name="mMMessage" rows="4"></textarea>
                        </div>
                    </div>
                </div>
                <div class="row" style="overflow: auto; max-height: 250px; width: 100%;">
                    <div class="col-md-12">
                        <table class="table table-striped table-bordered nowrap table-hover">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Télefono</th>
                                <th>Nombre</th>
                                <th>Folio</th>
                                <th>Paquetes</th>
                                <th>Guías</th>
                            </tr>
                            </thead>
                            <tbody id="tbl-list-package-sms">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btn-save-messages" type="button" class="btn btn-success" title="Enviar">Enviar</button>
                <button type="button" class="btn btn-danger" title="Cerrar" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>