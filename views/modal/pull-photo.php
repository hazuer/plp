<div class="modal fade" id="modal-pull-photo" tabindex="-1" role="dialog" aria-labelledby="modal-pull-photo-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><span id="modal-pull-photo-title"> </span></h3>
                <button id="stop-pull" type="button" class="close" data-dismiss="modal" aria-label="Close" title="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12" style="text-align: center;">
                        <video id="video-pull" width="320" height="240" autoplay></video>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12" style="text-align: center;">
                        <canvas id="canvas-pull" width="320" height="240"></canvas>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="snap-pull" type="button" class="btn btn-primary"><i class="fa fa-camera" aria-hidden="true"></i></button>
                    <button id="btn-photo-pull-save" type="button" class="btn btn-success" title="Liberar">Liberar</button>
                </div>
            </div>
        </div>
    </div>
</div>