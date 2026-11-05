this.$js.confirmDelete = (id, nama) => {
    $tsui.interaction('dialog')
        .wireable()
        .question('Hapus ' + nama + ' ?', 'Data yang sudah dihapus tidak dapat dikembalikan')
        .cancel('Batal')
        .confirm('Ya, Hapus', 'delete', id)
        .send();
}