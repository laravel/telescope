import { createApp } from 'vue';
import Base from './base';
import axios from 'axios';
import Routes from './routes';
import { createRouter, createWebHistory } from 'vue-router';
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

const LOCALSTORAGE_AUTOLOAD_KEY = 'telescopeAutoLoadsNewEntries';

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

window.Popper = popper;

moment.tz.setDefault(Telescope.timezone);

window.Telescope.basePath = '/' + window.Telescope.path;

let routerBasePath = window.Telescope.basePath + '/';

if (window.Telescope.path === '' || window.Telescope.path === '/') {
    routerBasePath = '/';
    window.Telescope.basePath = '';
}

const router = createRouter({
    routes: Routes,
    history: createWebHistory(routerBasePath),
});

const app = createApp({
    data() {
        return {
            alert: {
                type: null,
                autoClose: 0,
                message: '',
                confirmationProceed: null,
                confirmationCancel: null,
            },

            autoLoadsNewEntries: localStorage[LOCALSTORAGE_AUTOLOAD_KEY] === '1',

            recording: Telescope.recording,
        };
    },

    created() {
        window.addEventListener('keydown', this.keydownListener);
    },

    unmounted() {
        window.removeEventListener('keydown', this.keydownListener);
    },

    methods: {
        autoLoadNewEntries() {
            this.autoLoadsNewEntries = !this.autoLoadsNewEntries;
            localStorage[LOCALSTORAGE_AUTOLOAD_KEY] = Number(this.autoLoadsNewEntries);
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

app.use(router);

app.component('vue-json-pretty', VueJsonPretty);
app.component('related-entries', relatedEntries);
app.component('index-screen', indexScreen);
app.component('preview-screen', previewScreen);
app.component('alert', alert);
app.component('copy-clipboard', copyClipboard);

app.mixin(Base);

app.mount('#telescope');
