import Vue from 'vue'
import Base from './base'
import Routes from './routes'
import VueRouter from 'vue-router'

require('bootstrap');

Vue.use(VueRouter);

window.Popper = require('popper.js').default;

const router = new VueRouter({
    routes: Routes,
    mode: 'history',
    base: '/telescope/',
});

Vue.component('loader', require('./components/loader.vue'));
Vue.component('table-card', require('./components/TableCard.vue'));

Vue.mixin(Base);

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
