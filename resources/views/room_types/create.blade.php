<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Room Type</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dropzone-container {
            border: 2px dashed #0d6efd;
            border-radius: 8px;
            background-color: #f8f9fa;
            cursor: pointer;
            text-align: center;
            padding: 30px 15px;
            position: relative;
        }
        .dropzone-container:hover {
            background-color: #e9ecef;
        }
        #image-preview {
            max-height: 200px;
            object-fit: cover;
            border-radius: 6px;
            display: none;
            margin: 0 auto;
        }
    </style>
</head>
<body class="bg-light p-4">
    <div class="container" style="max-width: 600px;">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Add New Room Type</h4>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('room-types.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Type Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Deluxe Suite" value="{{ old('name') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Base Price ($)</label>
                        <input type="number" step="0.01" name="base_price" class="form-control" placeholder="e.g. 75.00" value="{{ old('base_price') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Capacity (Persons)</label>
                        <input type="number" name="capacity" class="form-control" placeholder="e.g. 2" value="{{ old('capacity') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bed Type</label>
                        <input type="text" name="bed_type" class="form-control" placeholder="e.g. 1 King Bed" value="{{ old('bed_type') }}">
                    </div>

                    <!-- Drag & Drop Area -->
                    <div class="mb-3">
                        <label class="form-label">Room Photo</label>
                        <div class="dropzone-container" id="drop-area" onclick="document.getElementById('image-input').click()">
                            <div id="drop-text">
                                <p class="mb-1 fw-semibold text-secondary">Click or Drag & Drop photo here</p>
                                <small class="text-muted">PNG, JPG, or WEBP</small>
                            </div>
                            <img id="image-preview" class="img-fluid" alt="Preview">
                            <input type="file" id="image-input" name="image" class="d-none" accept="image/*">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Short description...">{{ old('description') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between pt-2">
                        <a href="{{ route('room-types.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Room Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('image-input');
        const preview = document.getElementById('image-preview');
        const dropText = document.getElementById('drop-text');

        ['dragenter', 'dragover'].forEach(eName => {
            dropArea.addEventListener(eName, (e) => {
                e.preventDefault();
                dropArea.style.backgroundColor = '#e2e6ea';
            });
        });

        ['dragleave', 'drop'].forEach(eName => {
            dropArea.addEventListener(eName, (e) => {
                e.preventDefault();
                dropArea.style.backgroundColor = '#f8f9fa';
            });
        });

        dropArea.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                showImage(files[0]);
            }
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                showImage(e.target.files[0]);
            }
        });

        function showImage(file) {
            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
            dropText.style.display = 'none';
        }
    </script>
</body>
</html>