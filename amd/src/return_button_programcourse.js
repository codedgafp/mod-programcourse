/**
 * Mod Program course JS.
 */
define([
    'jquery'
], function ($) {
    return {
        /**
         * Mod Program course JS init.
         *
         * @return void
         */
        init: function () {
         
            $(document).ready(function() {
                this.setProgramcourseButton();
            }.bind(this));
        },

        setProgramcourseButton: function () {
            var lastVisitedUrl = sessionStorage.getItem('lastVisitedUrl');

            if (lastVisitedUrl && lastVisitedUrl.includes('/course/view.php?id=')) {
                var previouscourseId = lastVisitedUrl.split('/course/view.php?id=')[1];
                var currentCourseId = window.location.search.split('id=')[1];
                $.ajax({
                    method: 'GET',
                    url: M.cfg.wwwroot + '/mod/programcourse/ajax/ajax.php',
                    data: {
                        controller: 'programcourse',
                        action: 'get_eligibility_for_programcourse',
                        format: 'json',
                        currentCourseId: currentCourseId,
                        previouscourseId: previouscourseId
                    },
                    error: function () {
                        console.log('error');
                    }
                }).done(function (response) {
                    response = JSON.parse(response);
                    if(response.message === true) {
                    const courseHeader = document.getElementById('course-header');
                    const button = document.createElement('button');
                    button.setAttribute('aria-label', M.util.get_string('return_parcours', 'mod_programcourse'));
                    button.innerHTML = M.util.get_string('return_parcours', 'mod_programcourse');
                    button.classList.add('btn', 'btn-secondary', 'btn-lg');
                    button.onclick = function () {
                        window.location.href = lastVisitedUrl;
                    };
                    courseHeader.insertBefore(button, courseHeader.firstChild);
                }});
                
            }           
        
        }
    };
});
