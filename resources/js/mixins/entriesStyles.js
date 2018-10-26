export default {
    methods: {
        cacheActionTypeClass(type){
            if (type === 'hit') return 'success';
            if (type === 'set') return 'info';
            if (type === 'forget') return 'warning';
            if (type === 'missed') return 'danger';
        },


        jobStatusClass(status){
            if (status === 'pending') return 'secondary';
            if (status === 'processed') return 'success';
            if (status === 'failed') return 'danger';
        },


        logLevelClass(level){
            if (level === 'debug') return 'success';
            if (level === 'info') return 'info';
            if (level === 'notice') return 'secondary';
            if (level === 'warning') return 'warning';
            if (level === 'error') return 'danger';
            if (level === 'critical') return 'danger';
            if (level === 'alert') return 'danger';
            if (level === 'emergency') return 'danger';
        },


        modelActionClass(action){
            if (action == 'created') return 'success';
            if (action == 'updated') return 'info';
            if (action == 'deleted' || action == 'forceDeleted') return 'danger';
        },


        requestStatusClass(status){
            if (status < 300) return 'secondary';
            if (status < 400) return 'info';
            if (status < 500) return 'warning';
            if (status >= 500) return 'danger';
        },


        requestMethodClass(method){
            if (method == 'GET') return 'secondary';
            if (method == 'OPTIONS') return 'secondary';
            if (method == 'POST') return 'info';
            if (method == 'PATCH') return 'info';
            if (method == 'PUT') return 'info';
            if (method == 'DELETE') return 'danger';
        }
    }
};
