import Vue from 'vue'
import VueRouter from 'vue-router'
import Routes from './routes'

require('bootstrap');

Vue.use(VueRouter);

window.Popper = require('popper.js').default;

const router = new VueRouter({
    routes: Routes,
    mode: 'history',
    base: '/telescope/',
});

Vue.component('loader', require('./components/loader.vue'));

const admin = new Vue({
    el: '#telescope',

    router,

    components: {},

    data() {
        return {
            loaded: true
        };
    },

    /**
     * The component has been created by Vue.
     */
    created() {

    },

    methods: {}
});
