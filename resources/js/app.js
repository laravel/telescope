import Vue from 'vue'
import Base from './base'
import Routes from './routes'
import VueRouter from 'vue-router'
import TreeView from "vue-json-tree-view"

require('bootstrap');

Vue.use(VueRouter);
Vue.use(TreeView);

window.Popper = require('popper.js').default;

const router = new VueRouter({
    routes: Routes,
    mode: 'history',
    base: '/' + window.Telescope.path + '/',
});

Vue.component('related-entries', require('./components/RelatedEntries.vue'));
Vue.component('index-screen', require('./components/IndexScreen.vue'));
Vue.component('preview-screen', require('./components/PreviewScreen.vue'));
Vue.component('alert', require('./components/Alert.vue'));

Vue.mixin(Base);

new Vue({
    el: '#telescope',

    router,

    data(){
        return {
            alert: {
                type: null,
                autoClose: 0,
                message: '',
                confirmationProceed: null,
                confirmationCancel: null,
            },

            autoLoadsNewEntries: localStorage.autoLoadsNewEntries === '1'
        }
    },

    methods: {
        autoLoadNewEntries(){
            if (!this.autoLoadsNewEntries) {
                this.autoLoadsNewEntries = true;
                localStorage.autoLoadsNewEntries = 1;
            } else {
                this.autoLoadsNewEntries = false;
                localStorage.autoLoadsNewEntries = 0;
            }
        }
    }
});
