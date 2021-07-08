import Vue from 'vue';
import Base from './base';
import axios from 'axios';
import Routes from './routes';
import VueRouter from 'vue-router';
import VueJsonPretty from 'vue-json-pretty';
import 'vue-json-pretty/lib/styles.css';
import moment from 'moment-timezone';

require('bootstrap');

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

Vue.use(VueRouter);

window.Popper = require('popper.js').default;

moment.tz.setDefault(Telescope.timezone);

window.Telescope.basePath = '/' + window.Telescope.path;

let routerBasePath = window.Telescope.basePath + '/';

if (window.Telescope.path === '' || window.Telescope.path === '/') {
    routerBasePath = '/';
    window.Telescope.basePath = '';
}

const router = new VueRouter({
    routes: Routes,
    mode: 'history',
    base: routerBasePath,
});

Vue.component('vue-json-pretty', VueJsonPretty);
Vue.component('related-entries', require('./components/RelatedEntries.vue').default);
Vue.component('index-screen', require('./components/IndexScreen.vue').default);
Vue.component('preview-screen', require('./components/PreviewScreen.vue').default);
Vue.component('alert', require('./components/Alert.vue').default);

Vue.mixin(Base);

new Vue({
    el: '#telescope',

    router,

    data() {
        return {
            alert: {
                type: null,
                autoClose: 0,
                message: '',
                confirmationProceed: null,
                confirmationCancel: null,
            },

            autoLoadsNewEntries: localStorage.autoLoadsNewEntries === '1',

            recording: Telescope.recording,
        };
    },

    methods: {
        autoLoadNewEntries() {
            if (!this.autoLoadsNewEntries) {
                this.autoLoadsNewEntries = true;
                localStorage.autoLoadsNewEntries = 1;
            } else {
                this.autoLoadsNewEntries = false;
                localStorage.autoLoadsNewEntries = 0;
            }
        },

        toggleRecording() {
            axios.post(Telescope.basePath + '/telescope-api/toggle-recording');

            window.Telescope.recording = !Telescope.recording;
            this.recording = !this.recording;
        },

        clearEntries() {
            if (confirm('Are you sure you want to delete all Telescope data?')) {
                axios.delete(Telescope.basePath + '/telescope-api/entries').then((response) => location.reload());
            }
        },
    },
});
