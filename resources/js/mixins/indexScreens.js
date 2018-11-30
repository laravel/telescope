import moment from 'moment-timezone';

export default {
    data() {
        return {
            displayVerboseTimes: localStorage.displayVerboseTimes === '1',
        }
    },

    methods: {
        /**
         * Toggle displaying verbose or terse times.
         */
        toggleVerboseTimes(){
            if (!this.displayVerboseTimes) {
                this.displayVerboseTimes = true;
                localStorage.displayVerboseTimes = 1;
            } else {
                this.displayVerboseTimes = false;
                localStorage.displayVerboseTimes = 0;
            }
        },

        /**
         * Format to verbose time
         */
        verboseTime(time){
            return moment(time + ' Z').utc().local().format('DD/MM/YY HH:mm:ss');
        },
    },

    computed: {
        /**
         * Check if terse times should be displayed.
         */
        displayTerseTimes(){
            return ! this.displayVerboseTimes;
        },
    },
}