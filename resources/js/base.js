import _ from 'lodash';
import moment from 'moment';

export default {
    methods: {
        /**
         * Show the time ago format for the given time.
         */
        timeAgo(time){
            return moment(time + ' Z').utc().local().fromNow(true);
        },


        /**
         * Truncate the given string.
         */
        truncate(string, length = 70){
            return _.truncate(string, {
                'length': length,
                'separator': /,? +/
            });
        },


        /**
         * Creates a debounced function that delays invoking a callback.
         */
        debouncer: _.debounce(callback => callback(), 500),
    }
};
