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
            
        var currentUrl = window.location.href;
        if (document.referrer === '' || document.referrer === currentUrl) {
            return;
        }
            if(currentUrl.includes('/course/view.php?id=') ) {
                var currentUrlParams = new URLSearchParams(window.location.search);
                var currentCourseId = currentUrlParams.get('id');
                     
                
                $.ajax({
                    method: 'GET',
                    url: M.cfg.wwwroot + '/mod/programcourse/ajax/ajax.php',
                    data: {
                        controller: 'programcourse',
                        action: 'get_eligibility_for_programcourse',
                        format: 'json',
                        currentCourseId: currentCourseId
                    },
                    error: function () {
                        console.log('error');
                    }
                }).done(function (response) {
                   
                    response = JSON.parse(response);
                    
                    if(response.message === true && response.redirectid != null) {
                    const courseHeader = document.getElementById('course-header');
                    const button = document.createElement('button');
                    button.setAttribute('aria-label', M.util.get_string('return_parcours', 'mod_programcourse'));
                    button.innerHTML = M.util.get_string('return_parcours', 'mod_programcourse');
                    button.classList.add('btn', 'btn-secondary', 'btn-lg');
                    button.onclick = function () {
                        window.location.href = M.cfg.wwwroot + '/course/view.php?id=' + response.redirectid;
                    };
                    courseHeader.insertBefore(button, courseHeader.firstChild);
                }});
                
            }           
        
        }
    };
});
