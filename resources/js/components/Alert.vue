<script type="text/ecmascript-6">
    import $ from 'jquery';

    export default {
        props: ['type', 'message', 'autoClose', 'confirmationProceed', 'confirmationCancel'],

        data(){
            return {
                timeout: null,
                anotherModalOpened: $('body').hasClass('modal-open')
            }
        },


        mounted() {
            $('#alertModal').modal({
                backdrop: 'static',
            });

            $('#alertModal').on('hidden.bs.modal', e => {
                this.$root.alert.type = null;
                this.$root.alert.autoClose = false;
                this.$root.alert.message = '';
                this.$root.alert.confirmationProceed = null;
                this.$root.alert.confirmationCancel = null;

                if (this.anotherModalOpened) {
                    $('body').addClass('modal-open');
                }
            });

            if (this.autoClose) {
                this.timeout = setTimeout(() => {
                    this.close();
                }, this.autoClose);
            }
        },


        methods: {
            /**
             * Close the modal.
             */
            close(){
                clearTimeout(this.timeout);

                $('#alertModal').modal('hide');
            },


            /**
             * Confirm and close the modal.
             */
            confirm(){
                this.confirmationProceed();

                this.close();
            },


            /**
             * Cancel and close the modal.
             */
            cancel(){
                if (this.confirmationCancel) {
                    this.confirmationCancel();
                }

                this.close();
            }
        }
    }
</script>

<template>
    <div class="modal" id="alertModal" tabindex="-1" role="dialog" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <p class="mt-3 mb-0">{{message}}</p>
                </div>


                <div class="modal-footer justify-content-center">

                    <button v-if="type == 'error'" class="btn btn-secondary btn-sm" @click="close">
                        CLOSE
                    </button>

                    <button v-if="type == 'success'" class="btn btn-secondary btn-sm" @click="close">
                        OK
                    </button>


                    <button v-if="type == 'confirmation'" class="btn btn-danger btn-sm" @click="confirm">
                        YES
                    </button>
                    <button v-if="type == 'confirmation'" class="btn btn-secondary btn-sm" @click="cancel">
                        NO, CANCEL
                    </button>

                </div>
            </div>
        </div>
    </div>
</template>

<style>
    #alertModal {
        z-index: 99999;
        background: rgba(0, 0, 0, 0.5);
    }
</style>
