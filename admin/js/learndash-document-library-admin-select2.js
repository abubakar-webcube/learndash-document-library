jQuery(function($){
    // $("#ldl_document_groups").select2({
    //     placeholder: "Select Groups",
    //     width: "100%",
    // });
    // $("#ldl_document_courses").select2({
    //     placeholder: "Select Courses",
    //     width: "100%",
    // });
    // $("#ldl_document_roles").select2({
    //     placeholder: "Select User Roles",
    //     width: "100%",
    // });
    
    $(document).ready(function(){
        $("#ldl_document_groups").select2({
            placeholder: "Select Groups",
            width: "100%",
        });
        $("#ldl_document_courses").select2({
            placeholder: "Select Courses",
            width: "100%",
        });
        $("#ldl_document_roles").select2({
            placeholder: "Select User Roles",
            width: "100%",
        });
        $("#ldl_global_user_roles").select2({
            placeholder: "Select User Roles",
            allowClear: true,
        });
        // learnDash select2
        /*
        $('#ldl_libraries_layout').select2({
            width: 'resolve',
            placeholder: "Select Layout",
        });
        $('.select2-container').removeClass('select2-container--default').addClass('select2-container--learndash');
        */
    });
});