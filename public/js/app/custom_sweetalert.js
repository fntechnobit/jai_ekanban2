const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000
});

function showConfirmationBeforeAction(title, text, confirmCallback, confirmButtonText = 'Yes, Delete', cancelButtonText = 'Cancel') {
    Swal.fire({
        title: title,
        text: text,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: confirmButtonText,
      }).then((result) => {
        if (result.isConfirmed) {
          confirmCallback();
        }
      });
}

function showToast(icon, title) {
    Toast.fire({
        icon: icon,
        title: title,
    });
}
