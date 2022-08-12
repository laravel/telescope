import _ from 'lodash';
import moment from 'moment-timezone';

export default {
    computed: {
        Telescope() {
            return Telescope;
        },
    },

    methods: {
        /**
         * Show the time ago format for the given time.
         */
        timeAgo(time) {
            moment.updateLocale(window.Telescope.locale, {
                relativeTime: {
                    future: this.__('in :time', { time: '%s' }),
                    past: this.__(':time ago', { time: '%s' }),
                    s: (number) => this.__(":time's ago", { time: number }),
                    ss: this.__(':seconds ago', { second: '%d' }),
                    m: this.__(':countm ago', { count: '1' }),
                    mm: this.__(':countm ago', { count: '%d' }),
                    h: this.__(':counth ago', { count: '1' }),
                    hh: this.__(':counth ago', { count: '%d' }),
                    d: this.__(':countd ago', { count: '1' }),
                    dd: this.__(':countd ago', { count: '%d' }),
                    M: this.__('a month ago'),
                    MM: this.__(':count months ago', { count: '%d' }),
                    y: this.__('a year ago'),
                    yy: this.__(':count years ago', { count: '%d' }),
                },
            });

            let secondsElapsed = moment().diff(time, 'seconds');
            let dayStart = moment('2018-01-01').startOf('day').seconds(secondsElapsed);

            if (secondsElapsed > 300) {
                return moment(time).fromNow(true);
            } else if (secondsElapsed < 60) {
                return dayStart.format('s') + 's ago';
            } else {
                return dayStart.format('m:ss') + 'm ago';
            }
        },

        /**
         * Show the time in local time.
         */
        localTime(time) {
            return moment(time).local().format('MMMM Do YYYY, h:mm:ss A');
        },

        /**
         * Truncate the given string.
         */
        truncate(string, length = 70) {
            return _.truncate(string, {
                length: length,
                separator: /,? +/,
            });
        },

        /**
         * Creates a debounced function that delays invoking a callback.
         */
        debouncer: _.debounce((callback) => callback(), 500),

        /**
         * Show an error message.
         */
        alertError(message) {
            this.$root.alert.type = 'error';
            this.$root.alert.autoClose = false;
            this.$root.alert.message = message;
        },

        /**
         * Show a success message.
         */
        alertSuccess(message, autoClose) {
            this.$root.alert.type = 'success';
            this.$root.alert.autoClose = autoClose;
            this.$root.alert.message = message;
        },

        /**
         * Show confirmation message.
         */
        alertConfirm(message, success, failure) {
            this.$root.alert.type = 'confirmation';
            this.$root.alert.autoClose = false;
            this.$root.alert.message = message;
            this.$root.alert.confirmationProceed = success;
            this.$root.alert.confirmationCancel = failure;
        },
    },
};
