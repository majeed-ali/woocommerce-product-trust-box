jQuery(function($){
  function openMedia(uploaderEl){
    const frame = wp.media({
      title: wcptbAdmin.mediaTitle,
      button: { text: wcptbAdmin.mediaButton },
      multiple: false
    });

    frame.on('select', function(){
      const attachment = frame.state().get('selection').first().toJSON();
      uploaderEl.find('.wcptb-attach-id').val(attachment.id).trigger('change');

      const preview = uploaderEl.find('.wcptb-preview');
      preview.html('<img src="'+attachment.sizes.thumbnail.url+'" alt="" />');
    });

    frame.open();
  }

  $(document).on('click', '.wcptb-upload', function(e){
    e.preventDefault();
    const uploaderEl = $(this).closest('.wcptb-uploader');
    openMedia(uploaderEl);
  });

  $(document).on('click', '.wcptb-remove', function(e){
    e.preventDefault();
    const uploaderEl = $(this).closest('.wcptb-uploader');
    uploaderEl.find('.wcptb-attach-id').val('').trigger('change');
    uploaderEl.find('.wcptb-preview').html('<em>—</em>');
  });
});
