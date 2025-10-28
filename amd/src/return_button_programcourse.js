/**
 * Mod Program course JS.
 */
define([
    'jquery'
], function ($) {
    const courseViewRegex = /\/course\/view\.php\?id=\d+/;
    const courseViewSectionRegex = /\/course\/section\.php\?id=\d+/;

    return {
        /**
         * Mod Program course JS init.
         *
         * @return void
         */
        init: function () {
            $(document).ready(function () {
                this.setProgramcourseButton();
            }.bind(this));
        },

        setProgramcourseButton: function () {
            let currentUrl = window.location.href;

            if (document.referrer === '' || document.referrer === currentUrl) {
                return;
            }

            if (this.isSessionPage(currentUrl)) {
                let currentUrlParams = new URLSearchParams(window.location.search);
                let currentId = currentUrlParams.get('id');

                let currentCourseIsSection = courseViewSectionRegex.test(currentUrl);

                $.ajax({
                    method: 'GET',
                    url: M.cfg.wwwroot + '/mod/programcourse/ajax/ajax.php',
                    data: {
                        controller: 'programcourse',
                        action: 'get_eligibility_for_programcourse',
                        format: 'json',
                        currentId: currentId,
                        currentCourseIsSection: currentCourseIsSection
                    },
                    error: function () {
                        console.log('error');
                    }
                }).done(function (response) {
                    response = JSON.parse(response);

                    if (response.message === true && response.redirectid != null) {
                        const courseHeader = document.getElementById('course-header');
                        const button = document.createElement('button');

                        button.setAttribute('aria-label', M.util.get_string('return_parcours', 'mod_programcourse'));
                        button.innerHTML = M.util.get_string('return_parcours', 'mod_programcourse');
                        button.classList.add('btn', 'btn-secondary', 'btn-lg');

                        button.onclick = function () {
                            window.location.href = M.cfg.wwwroot + '/course/view.php?id=' + response.redirectid;
                        };

                        courseHeader.insertBefore(button, courseHeader.firstChild);
                    }
                });
            }
        },

        isSessionPage: function (currentUrl) {
            return (
                courseViewRegex.test(currentUrl) ||
                courseViewSectionRegex.test(currentUrl)
            );
        }
    };
});
