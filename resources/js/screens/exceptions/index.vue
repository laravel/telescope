<script type="text/ecmascript-6">
export default {}
</script>

<template>
    <index-screen title="Exceptions" resource="exceptions">
        <tr slot="table-header">
            <th scope="col" v-if="!$route.query.family_hash">Type</th>
            <th scope="col" class="text-right" v-if="!$route.query.family_hash && !$route.query.tag">#</th>
            <th scope="col" v-if="$route.query.family_hash">Message</th>
            <th scope="col" class="text-right">Happened</th>
            <th scope="col">Resolved</th>
            <th scope="col"></th>
        </tr>

        <template slot="row" slot-scope="slotProps">
            <td :title="slotProps.entry.content.class" v-if="!$route.query.family_hash">
                {{ truncate(slotProps.entry.content.class, 70) }}<br />

                <small class="text-muted">{{ truncate(slotProps.entry.content.message, 100) }}</small>
            </td>

            <td class="table-fit text-right text-muted" v-if="!$route.query.family_hash && !$route.query.tag">
                <span>{{ slotProps.entry.content.occurrences }}</span>
            </td>

            <td :title="slotProps.entry.content.message" v-if="$route.query.family_hash">
                {{ truncate(slotProps.entry.content.message, 80) }}<br />

                <small class="text-muted">
                    <span v-if="slotProps.entry.content.user && slotProps.entry.content.user.email">
                        User: {{ slotProps.entry.content.user.email }} ({{ slotProps.entry.content.user.id }})
                    </span>

                    <span v-else> User: N/A </span>
                </small>
            </td>

            <td
                class="table-fit text-right text-muted"
                :data-timeago="slotProps.entry.created_at"
                :title="slotProps.entry.created_at"
            >
                {{ timeAgo(slotProps.entry.created_at) }}
            </td>

            <td class="table-fit">
                <div
                    v-if="slotProps.entry.content.resolved_at"
                    :data-timeago="slotProps.entry.content.resolved_at"
                    :title="slotProps.entry.content.resolved_at"
                >
                    {{ timeAgo(slotProps.entry.content.resolved_at) }}
                </div>
                <div v-if="!slotProps.entry.content.resolved_at" class="control-action text-center">
                    <svg
                        viewBox="0 0 20 20"
                        version="1.1"
                        xmlns="http://www.w3.org/2000/svg"
                        xmlns:xlink="http://www.w3.org/1999/xlink"
                    >
                        <path
                            fill="#ef5753"
                            d="M2.92893219,17.0710678 C6.83417511,20.9763107 13.1658249,20.9763107 17.0710678,17.0710678 C20.9763107,13.1658249 20.9763107,6.83417511 17.0710678,2.92893219 C13.1658249,-0.976310729 6.83417511,-0.976310729 2.92893219,2.92893219 C-0.976310729,6.83417511 -0.976310729,13.1658249 2.92893219,17.0710678 Z M9,5 L11,5 L11,11 L9,11 L9,5 Z M9,13 L11,13 L11,15 L9,15 L9,13 Z"
                            id="Combined-Shape"
                        ></path>
                    </svg>
                </div>
            </td>

            <td class="table-fit">
                <router-link
                    :to="{
                        name: 'exception-preview',
                        params: { id: slotProps.entry.id },
                    }"
                    class="control-action"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.75 9.25a.75.75 0 000 1.5h4.59l-2.1 1.95a.75.75 0 001.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 10-1.02 1.1l2.1 1.95H6.75z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </router-link>
            </td>
        </template>
    </index-screen>
</template>
