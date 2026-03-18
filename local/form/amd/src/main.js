define(['jquery', 'core/config'], function ($, cfg) {
    return {
        init: function () {
            $(document).ready(function () {
                // Update states when the country changes
                $(document).on('change', '#country_select', function () {
                    var countryId = $('#country_select').val();

                    // Clear state and district selects
                    $('#state_select').html('<option value="">Select a state</option>');
                    $('#district_select').html('<option value="">Select a district</option>');

                    if (countryId) {
                        $.ajax({
                            type: "POST",
                            url: "ajax.php",
                            data: {
                                action: 'getStates',
                                country_id: countryId
                            },
                            success: function (result) {
                                $('#state_select').html(result);
                            },
                            error: function (xhr, status, error) {
                                console.error("Error fetching states: ", error);
                            }
                        });
                    }
                });

                // Update districts when the state changes
                $(document).on('change', '#state_select', function () {
                    var stateId = $('#state_select').val();

                    // Clear district select
                    $('#district_select').html('<option value="">Select a district</option>');

                    if (stateId) {
                        $.ajax({
                            type: "POST",
                            url: "ajax.php",
                            data: {
                                action: 'getDistricts',
                                state_id: stateId
                            },
                            success: function (result) {
                                $('#district_select').html(result);
                            },
                            error: function (xhr, status, error) {
                                console.error("Error fetching districts: ", error);
                            }
                        });
                    }
                });
            });
        },

        formoperation: function () {
            $(document).ready(function () {
                $(document).on("click", ".page-link", function (e) {
                    e.preventDefault();
                    var page_val = $(this).attr("href");
                    var url = new URL(page_val);
                    var page = url.searchParams.get("page");
                    $.ajax({
                        url: "ajax.php", // Adjust the URL as needed.
                        type: "POST",
                        data: {
                            action: 'pagination',
                            page: page
                        },
                        success: function (data) {
                            $("#localformlist").html(data);
                        },
                        error: function (xhr, status, error) {
                            console.error("AJAX Request Error: " + error);
                        },
                    });
                });
            });

            $(document).on('click', '.visible_form', function () {
                var visibleid = $(this).attr('id');
                var ajaxurl = $('.ajaxurl').val();
                $.ajax({
                    type: 'POST',
                    data: { 'visibleid': visibleid, action: 'visible_form' },
                    url: "ajax.php",
                    success: function (data) {
                        location.reload();
                    }
                }); //ajax ends here
            }); //visible skill end here

            $(document).on('click', '.moveup', function () {
                var id = $(this).attr('id');
                var tablename = $(this).attr('tablename');
                var moveupid = $(this).attr('moveup');
                $.ajax({
                    type: 'POST',
                    data: { 'id': id, tablename: tablename, moveupid: moveupid, action: 'moveup' },
                    url: "ajax.php",
                    success: function (data) {
                        location.reload();
                    }
                });
            });

            $(document).on('click', '.movedown', function (e) {
                e.preventDefault();
                var id = $(this).attr('id');
                var tablename = $(this).attr('tablename');
                var movedownid = $(this).attr('movedown');
                $.ajax({
                    type: 'POST',
                    data: { 'id': id, tablename: tablename, movedownid: movedownid, action: 'movedown' },
                    url: "ajax.php",
                    success: function (data) {
                        location.reload();
                    }
                });
            });

            $(document).on('click', '#add_to_cohort_button', function () {
                var country_id = $(this).val();
                $.ajax({
                    type: "POST",
                    url: "ajax.php",
                    data: {
                        country_id: country_id,

                    },
                    success: function (result) {
                        $('#id_section').html(result);
                    },
                    error: function () {
                        console.error(result);
                    }
                });
            });
        },

        //  inactive formlist pagination

        inactiveformlist: function () {
            $(document).ready(function () {
                $(document).on("click", ".page-link", function (e) {
                    e.preventDefault();
                    var page_val = $(this).attr("href");
                    var url = new URL(page_val);
                    var page = url.searchParams.get("page");
                    $.ajax({
                        url: "ajax.php", // Adjust the URL as needed.
                        type: "POST",
                        data: {
                            action: 'inactiveformlist',
                            page: page
                        },
                        success: function (data) {
                            $("#inactiveform_list").html(data);
                        },
                        error: function (xhr, status, error) {
                            console.error("AJAX Request Error: " + error);
                        },
                    });
                });
            });


        },

        //  inactive formlist pagination
        sendmail: function () {
            $(document).ready(function () {
                $(document).on("click", ".page-link", function (e) {
                    e.preventDefault();
                    var page_val = $(this).attr("href");
                    var url = new URL(page_val);
                    var page = url.searchParams.get("page");
                    var urlParams = new URLSearchParams(window.location.search);
                    var formid = urlParams.get('formid');  // Extracts the formid from the URL
                    $.ajax({
                        url: "ajax.php", // Adjust the URL as needed.
                        type: "POST",
                        data: {
                            action: 'paginaton_mail',
                            page: page,
                            formid: formid,
                        },
                        success: function (data) {
                            $("#send_mailuser").html(data);
                        },
                        error: function (xhr, status, error) {
                            console.error("AJAX Request Error: " + error);
                        },
                    });
                });

                $(document).on("click", "#mailtouser", function (e) {
                    e.preventDefault();
                    var urlParams = new URLSearchParams(window.location.search);
                    var formid = urlParams.get('formid');  // Extracts the formid from the URL
                    // Show confirmation popup
                    if (confirm("Are you sure you want to send the email?")) {
                        $.ajax({
                            url: "ajax.php", // Adjust the URL as needed.
                            type: "POST",
                            data: {
                                action: 'user_mail',
                                formid: formid,

                            },
                            success: function (data) {
                            },
                            error: function (xhr, status, error) {
                                console.error("AJAX Request Error: " + error);
                            },
                        });
                    } else {
                        alert("Email sending canceled.");
                    }
                });

            });


        },


        form: function () {

            $(document).off('click.removeusers')
                .on('click.removeusers', '#add_to_cohort_button', function (e) {

                    e.preventDefault();

                    var cohortId = $('#quizviewfilter').val();
                    var selectedUsers = [];

                    $('.uidcheckbox:checked').each(function () {
                        selectedUsers.push($(this).val());
                    });

                    if (!selectedUsers.length) {
                        alert('Please select at least one user.');
                        return;
                    }

                    if (!cohortId) {
                        alert('Please select a cohort.');
                        return;
                    }

                    $.ajax({
                        type: 'POST',
                        url: cfg.wwwroot + '/local/form/usercohort.php',
                        dataType: 'json',
                        data: {
                            sesskey: cfg.sesskey,
                            cohortId: cohortId,
                            selectedUsers: selectedUsers
                        },
                        success: function (res) {

                            console.log(res);

                            if (res.status === 'success') {

                                alert(res.message);

                                window.location.href = window.location.href;

                            } else {
                                alert(res.message);
                            }
                        },
                        error: function (xhr) {

                            console.log(xhr.responseText);
                            alert('Server error. Check console.');
                        }
                    });

                });
        },

        confirmtoggle: function () {

            $(document).on('click', '.confirm_toggle', function () {

                var button = $(this);
                var uid = button.data('uid');
                var time = button.data('time');
                var currentStatus = button.data('status');

                var newStatus = (currentStatus === 'Confirmed')
                    ? 'Not Confirmed'
                    : 'Confirmed';
                $.ajax({
                    type: "POST",
                    url: cfg.wwwroot + '/local/form/toggle_confirm.php',
                    dataType: "json",
                    data: {
                        action: 'toggleconfirm',
                        uid: uid,
                        timecreated: time,
                        status: newStatus,
                        sesskey: cfg.sesskey,
                        formid: $('#ajax_formid').val(), // hidden input

                    },
                    success: function (res) {

                        if (res.status === 'success') {

                            button.data('status', newStatus);

                            if (newStatus === 'Confirmed') {
                                button.removeClass('btn-danger')
                                    .addClass('btn-success')
                                    .text('Confirmed');
                            } else {
                                button.removeClass('btn-success')
                                    .addClass('btn-danger')
                                    .text('Not Confirmed');
            location.reload();
                            }
                        }
                    }
                });

            });
        },
        filterrecords: function () {

            function loadRecords(page = 0) {

                $.ajax({
                    type: "POST",
                    url: cfg.wwwroot + '/local/form/toggle_confirm.php',
                    dataType: "json",
                    data: {
                        sesskey: cfg.sesskey,
                        action: 'loadrecords', // changed
                        keyword: $('#table_search').val().trim(),
                        formid: $('#ajax_formid').val(),
                        page: page,
                        perpage: parseInt($('#ajax_perpage').val()) || 50,
                        cohortid: $('#ajax_cohortid').val(),
                        token: $('#ajax_token').val()
                    },

                    beforeSend: function () {
                        $('#records_container').html(
                            '<div class="text-center p-3">Loading...</div>'
                        );
                    },

                    success: function (response) {
                        $('#records_container').html(response.html);
                    },

                    error: function () {
                        $('#records_container').html(
                            '<div class="alert alert-danger">Error loading data</div>'
                        );
                    }
                });
            }

            /* APPLY */
            $(document).on('click', '#apply_search', function () {
                loadRecords(0);
            });

            /* RESET */
            $(document).on('click', '#reset_search', function () {
                $('#table_search').val('');
                loadRecords(0);
            });

            /* ENTER */
            $(document).on('keypress', '#table_search', function (e) {
                if (e.which === 13) {
                    loadRecords(0);
                }
            });

        },
    };
});
