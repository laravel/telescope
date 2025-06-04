import Vue from 'vue';
import Base from './base';
import axios from 'axios';
import Routes from './routes';
import VueRouter from 'vue-router';
import VueJsonPretty from 'vue-json-pretty';
import 'vue-json-pretty/lib/styles.css';
import moment from 'moment-timezone';
import popper from 'popper.js';
import relatedEntries from './components/RelatedEntries.vue';
import indexScreen from './components/IndexScreen.vue';
import previewScreen from './components/PreviewScreen.vue';
import alert from './components/Alert.vue';
import copyClipboard from './components/CopyClipboard.vue';

import 'bootstrap';

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

Vue.use(VueRouter);

window.Popper = popper;

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
Vue.component('related-entries', relatedEntries);
Vue.component('index-screen', indexScreen);
Vue.component('preview-screen', previewScreen);
Vue.component('alert', alert);
Vue.component('copy-clipboard', copyClipboard);

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

    created() {
        window.addEventListener('keydown', this.keydownListener);
    },

    destroyed() {
        window.removeEventListener('keydown', this.keydownListener);
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

        clearEntries(shouldConfirm = true) {
            if (shouldConfirm && !confirm('Are you sure you want to delete all Telescope data?')) {
                return;
            }

            axios.delete(Telescope.basePath + '/telescope-api/entries').then((response) => location.reload());
        },

        keydownListener(event) {
            if (event.metaKey && event.key === 'k') {
                this.clearEntries(false);
            }
        },
    },
});
