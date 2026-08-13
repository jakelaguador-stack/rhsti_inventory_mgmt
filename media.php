<?php
  $page_title = 'All Image';
  require_once('includes/load.php');
  // Check what level user has permission to view this page
  page_require_level(2);
?>
<?php $media_files = find_all('media');?>
<?php
  if(isset($_POST['submit'])) {
    $photo = new Media();
    // Tinitiyak na kinukuha nito ang tamang file input mula sa form
    $photo->upload($_FILES['file_upload']);
    if($photo->process_media()){
        $session->msg('s','Photo has been uploaded successfully.');
        redirect('media.php');
    } else{
      $session->msg('d', join($photo->errors));
      redirect('media.php');
    }
  }
?>
<?php include_once('layouts/header.php'); ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
  body {
    background-color: #f8fafc;
  }
  .custom-card {
    border: none;
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 10px 30px rgba(162, 171, 187, 0.15);
    overflow: hidden;
  }
  .upload-zone {
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
  }
  .upload-zone:hover {
    border-color: #7c3aed;
    background: #f5f3ff;
  }
  .media-card {
    border: none;
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(162, 171, 187, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
    position: relative;
  }
  .media-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(162, 171, 187, 0.2);
  }
  .media-img-wrapper {
    height: 180px;
    overflow: hidden;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
  }
  .media-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
  }
  .media-card:hover .media-img {
    transform: scale(1.08);
  }
  .action-overlay {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 10;
  }
  .btn-delete-media {
    background: rgba(255, 255, 255, 0.9);
    color: #dc2626;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    transition: all 0.2s;
  }
  .btn-delete-media:hover {
    background: #dc2626;
    color: #ffffff;
  }
  .gradient-btn {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    border: none;
    color: white;
    font-weight: 600;
  }
  .gradient-btn:hover {
    opacity: 0.9;
    color: white;
  }
  .text-truncate-custom {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
    font-size: 0.85rem;
  }
</style>

<div class="container-fluid py-5" style="max-width: 1400px;">
  <div class="row mb-4">
     <div class="col-md-6">
       <?php echo display_msg($msg); ?>
     </div>
  </div>

  <div class="card custom-card">
    <div class="card-header bg-white py-4 px-4 border-0 border-bottom">
      <div class="row align-items-center g-3">
        <div class="col-md-4">
          <h4 class="mb-0 fw-bold text-dark d-flex align-items-center">
            <i class="fas fa-images text-purple me-2" style="color: #7c3aed;"></i> Photo Gallery
          </h4>
          <p class="text-muted small mb-0">Manage and upload your product images</p>
        </div>
        <div class="col-md-8">
          <form action="media.php" method="POST" enctype="multipart/form-data" class="row g-2 justify-content-md-end align-items-center">
            <div class="col-auto">
              <div class="upload-zone py-2 px-3 d-flex align-items-center gap-2">
                <i class="fas fa-cloud-arrow-up text-muted"></i>
                <input type="file" name="file_upload" id="file_upload" class="form-control form-control-sm border-0 bg-transparent p-0" accept="image/*" required style="max-width: 250px;"/>
              </div>
            </div>
            <div class="col-auto">
              <button type="submit" name="submit" class="btn gradient-btn px-4 py-2 rounded-3 shadow-sm">
                <i class="fas fa-upload me-2"></i> Upload
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="card-body p-4 bg-light-smooth">
      <?php if(empty($media_files)): ?>
        <div class="text-center py-5 text-muted">
          <img src="https://cdn-icons-png.flaticon.com/512/3342/3342137.png" alt="No Photos" style="width: 80px; opacity: 0.4;" class="mb-3 d-block mx-auto">
          <h5 class="fw-bold">No Photos Uploaded Yet</h5>
          <p class="small">Use the upload tool above to start adding media files.</p>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($media_files as $media_file): ?>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
              <div class="card media-card">
                <div class="action-overlay">
                  <a href="delete_media.php?id=<?php echo (int) $media_file['id'];?>" class="btn-delete-media" data-bs-toggle="tooltip" title="Delete Image" onclick="return confirm('Sigurado ka bang buburahin mo ang larawang ito?');">
                    <i class="fas fa-trash-can small"></i>
                  </a>
                </div>
                
                <div class="media-img-wrapper">
                  <img src="uploads/products/<?php echo $media_file['file_name'];?>" class="media-img" alt="<?php echo $media_file['file_name'];?>" loading="lazy" />
                </div>
                
                <div class="card-body p-2 text-center border-top">
                  <p class="fw-bold text-dark mb-0 text-truncate-custom" title="<?php echo $media_file['file_name'];?>">
                    <?php echo $media_file['file_name'];?>
                  </p>
                  <span class="badge bg-light text-muted border mt-1" style="font-size: 0.7rem; font-weight: 500;">
                    <?php echo strtoupper(str_replace('image/', '', $media_file['file_type']));?>
                  </span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>