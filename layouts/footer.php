     </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
  <script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/js/bootstrap-datepicker.min.js"></script>
  <script type="text/javascript" src="libs/js/functions.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var zoomModal = document.createElement('div');
      zoomModal.id = 'zoom-image-modal';
      zoomModal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);display:none;align-items:center;justify-content:center;z-index:1050;cursor:zoom-out;';
      zoomModal.innerHTML = '<img id="zoom-image-modal-img" src="" alt="Zoomed Image" style="max-width:90%;max-height:90%;box-shadow:0 0 20px rgba(0,0,0,0.5);border:4px solid #fff;border-radius:8px;" />';
      document.body.appendChild(zoomModal);

      var zoomImage = document.getElementById('zoom-image-modal-img');
      zoomModal.addEventListener('click', function () {
        zoomModal.style.display = 'none';
        zoomImage.src = '';
      });

      var zoomableImages = document.querySelectorAll('.zoomable-product-image');
      zoomableImages.forEach(function (img) {
        img.addEventListener('click', function () {
          var src = img.getAttribute('data-full-src') || img.src;
          zoomImage.src = src;
          zoomModal.style.display = 'flex';
        });
      });
    });
  </script>
  </body>
</html>

<?php if(isset($db)) { $db->db_disconnect(); } ?>
