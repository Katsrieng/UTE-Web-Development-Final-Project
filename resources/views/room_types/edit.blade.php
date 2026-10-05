<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room Type - {{ $roomType->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dropzone-container {
            border: 2px dashed #0d6efd;
            border-radius: 8px;
            background-color: #f8f9fa;
            cursor: pointer;
            text-align: center;
            padding: 25px 15px;
            position: relative;
            transition: background-color 0.2s ease;
        }
        .dropzone-container:hover {
            background-color: #e9ecef;
        }
        #image-preview {
            max-height: 200px;
            object-fit: cover;
            border-radius: 6px;
            margin: 0 auto;
            display: block;
        }
    </style>
</head>
<body class="bg-light p-4">
    <div class="container" style="max-width: 600px;">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Edit Room Type - {{ $roomType->name }}</h4>
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

                <form action="{{ route('room-types.update', $roomType) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Type Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $roomType->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Base Price ($)</label>
                        <input type="number" step="0.01" name="base_price" class="form-control" value="{{ old('base_price', $roomType->base_price) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Capacity (Persons)</label>
                        <input type="number" name="capacity" class="form-control" value="{{ old('capacity', $roomType->capacity) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bed Type</label>
                        <input type="text" name="bed_type" class="form-control" value="{{ old('bed_type', $roomType->bed_type) }}">
                    </div>

                    <!-- Drag & Drop Photo Upload -->
                    <div class="mb-3">
                        <label class="form-label">Room Photo</label>
                        <div class="dropzone-container" id="drop-area" onclick="document.getElementById('image-input').click()">
                            <div id="drop-text" class="{{ $roomType->image ? 'd-none' : '' }}">
                                <p class="mb-1 fw-semibold text-secondary">Click or Drag & Drop new photo here</p>
                                <small class="text-muted">PNG, JPG, or WEBP (Leave empty to keep current photo)</small>
                            </div>

                            @if($roomType->image)
                                <img id="image-preview" src="{{ str_starts_with($roomType->image, 'http') ? $roomType->image : asset($roomType->image) }}" class="img-fluid" alt="Room Photo">
                                <small id="change-text" class="text-primary d-block mt-2">Click or drag a new image to replace</small>
                            @else
                                <img id="image-preview" class="img-fluid d-none" alt="Preview">
                            @endif

                            <input type="file" id="image-input" name="image" class="d-none" accept="image/*">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $roomType->description) }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between pt-2">
                        <a href="{{ route('room-types.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Room Type</button>
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
        const changeText = document.getElementById('change-text');

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
            preview.classList.remove('d-none');
            preview.style.display = 'block';
            if (dropText) dropText.classList.add('d-none');
            if (changeText) changeText.innerText = 'New photo selected (click or drag to change again)';
        }
    </script>
</body>
</html>