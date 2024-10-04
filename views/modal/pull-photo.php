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
                    <video id="video-pull" with="1024" height="819" autoplay></video>
                </div>
                <div class="row">
                    <canvas id="canvas-pull" with="1024" height="819"></canvas>
                </div>
                <div class="modal-footer">
                    <button id="btn-photo-pull-save" type="button" class="btn btn-success" title="Liberar">Liberar</button>
                </div>
            </div>
        </div>
    </div>
</div>