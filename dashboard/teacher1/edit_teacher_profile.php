<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="mb-3">
    <label for="profile_photo" class="form-label">Profile Photo</label>
    <input type="file" class="form-control" name="profile_photo" id="profile_photo" accept="image/*" onchange="previewImage(event)">
    <img id="preview" src="<?= $teacher['profile_photo'] ? 'uploads/' . $teacher['profile_photo'] : 'https://via.placeholder.com/150' ?>" alt="Preview" class="mt-3 rounded" style="max-width: 200px; height: auto;">
</div>
<script>
function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function () {
        const preview = document.getElementById('preview');
        preview.src = reader.result;
    }
    reader.readAsDataURL(event.target.files[0]);
}
</script>

</body>
</html>