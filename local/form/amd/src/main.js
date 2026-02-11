define(['jquery', 'core/log'], function ($, log) {
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
                alert(country_id);
                $.ajax({
                    type: "POST",
                    url: "ajax.php",
                    data: {
                        country_id: country_id,

                    },
                    success: function (result) {
                        alert(result);
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
                    // alert(formid);
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
                                alert(data); // Show success message
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
            $(document).ready(function () {
                $(document).on('click', '#add_to_cohort_button', function () {
                    // e.preventDefault();
                    // Get selected dropdown value
                    var cohortId = $('#quizviewfilter').val();

                    // Get all checked checkboxes
                    var selectedUsers = [];
                    $('.uidcheckbox:checked').each(function () {
                        selectedUsers.push($(this).val());
                    });

                    // Ensure both dropdown and checkboxes have values
                    if (selectedUsers.length === 0) {
                        alert('Please select at least one user.');
                        return;
                    }
                    if (!cohortId) {
                        alert('Please select a cohort.');
                        return;
                    }

                    // Send data via AJAX
                    $.ajax({
                        type: "POST",
                        url: "usercohort.php",
                        data: {
                            cohortId: cohortId,
                            selectedUsers: selectedUsers
                        },
                        success: function (response) {
                            // alert(response);
                            var res = JSON.parse(response); // Parse the JSON response
                            if (res.status === 'success') {
                                alert(res.message);  // Show success message returned from PHP
                                location.reload();  // Optionally reload the page (or update the UI as needed)
                            } else {
                                alert(res.message);  // Show error message returned from PHP
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Error:', error);
                            alert('An error occurred while adding users to the cohort.');
                        }
                    });
                });

            });
        },

    };
});
