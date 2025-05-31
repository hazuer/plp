<div class="modal fade" id="modal-puppeteer" tabindex="-1" role="dialog" aria-labelledby="modal-puppeteer-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><span id="modal-puppeteer-title"> </span></h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="mpptIdLocation"><b>Ubicación:</b></label>
                            <select name="mpptIdLocation" id="mpptIdLocation" class="form-control" disabled>
                                <option value="1">Tlaquiltenango</option>
                                <option value="2">Zacatepec</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="mpptIdCatParcel"><b>Paquetería:</b></label>
                            <select name="mpptIdCatParcel" id="mpptIdCatParcel" class="form-control">
                                <option value="1">J&T</option>
                                <option value="2">IMILE</option>
                                <option value="3">CNMEX</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                             <label for="mpptIdMarcador"><b>Marcador:</b></label>
                                <select name="mpptIdMarcador" id="mpptIdMarcador" class="form-control">
                                <option value="black" style="background-color:black;">Negro</option>
                                <option value="red" style="background-color:red;">Rojo</option>
                                <option value="blue" style="background-color:blue;">Azul</option>
                                <option value="green" style="background-color:green;">Verde</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="mpptListTracking"><b>* Lista de Guías (Excel):</b></label>
                            <textarea class="form-control" id="mpptListTracking" name="mpptListTracking" rows="4"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btn-puppeteer-command" type="button" class="btn btn-success" title="Crear">Crear</button>
                <button type="button" class="btn btn-danger" title="Cerrar" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>