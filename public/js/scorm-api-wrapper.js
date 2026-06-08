/**
 * SCORM 1.2 API Wrapper
 */
var API = {
    cmi: {
        core: {
            lesson_status: 'not attempted',
            score: { raw: '' },
            session_time: '',
            exit: ''
        },
        suspend_data: ''
    },
    initialized: false,
    curriculumId: null,
    csrfToken: '',
    baseUrl: '',

    init: function(curriculumId, csrfToken, baseUrl) {
        this.curriculumId = curriculumId;
        this.csrfToken = csrfToken;
        this.baseUrl = baseUrl;
    },

    LMSInitialize: function(dummyString) {
        console.log("LMSInitialize called");
        this.initialized = true;

        // Fetch existing progress from server
        var xhr = new XMLHttpRequest();
        xhr.open('GET', this.baseUrl + '/scorm/progress/' + this.curriculumId, false); // Synchronous to ensure data is loaded
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send();

        if (xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            if (response.success && response.cmi) {
                this.cmi.core.lesson_status = response.cmi.core.lesson_status || 'incomplete';
                this.cmi.core.score.raw = response.cmi.core.score.raw || '';
                this.cmi.suspend_data = response.cmi.suspend_data || '';
            }
        }

        return "true";
    },

    LMSGetValue: function(varName) {
        console.log("LMSGetValue called for: " + varName);
        if (!this.initialized) return "";

        var parts = varName.split('.');
        var current = this;
        for (var i = 0; i < parts.length; i++) {
            if (current[parts[i]] !== undefined) {
                current = current[parts[i]];
            } else {
                return "";
            }
        }
        return current.toString();
    },

    LMSSetValue: function(varName, varValue) {
        console.log("LMSSetValue called for: " + varName + " = " + varValue);
        if (!this.initialized) return "false";

        if (varName === 'cmi.core.lesson_status') {
            this.cmi.core.lesson_status = varValue;
        } else if (varName === 'cmi.core.score.raw') {
            this.cmi.core.score.raw = varValue;
        } else if (varName === 'cmi.core.session_time') {
            this.cmi.core.session_time = varValue;
        } else if (varName === 'cmi.suspend_data') {
            this.cmi.suspend_data = varValue;
        } else if (varName === 'cmi.core.exit') {
            this.cmi.core.exit = varValue;
        }

        return "true";
    },

    LMSCommit: function(dummyString) {
        console.log("LMSCommit called");
        if (!this.initialized) return "false";

        // Save progress to server
        var xhr = new XMLHttpRequest();
        xhr.open('POST', this.baseUrl + '/scorm/progress/' + this.curriculumId, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        var data = JSON.stringify({ cmi: this.cmi });
        xhr.send(data);

        return "true";
    },

    LMSFinish: function(dummyString) {
        console.log("LMSFinish called");
        this.LMSCommit("");
        this.initialized = false;
        
        // Notify parent window that SCORM finished so we can reload progress
        if (window.parent && window.parent.Livewire) {
            window.parent.dispatchEvent(new CustomEvent('scorm-finished', { detail: { status: this.cmi.core.lesson_status, score: this.cmi.core.score.raw } }));
        }
        return "true";
    },

    LMSGetLastError: function() {
        return "0"; // No error
    },

    LMSGetErrorString: function(errorCode) {
        return "No error";
    },

    LMSGetDiagnostic: function(errorCode) {
        return "No error";
    }
};

window.API = API; // Standard SCORM 1.2 API object
