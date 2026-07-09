<script>
import { layoutMethods,layoutComputed } from "@/state/helpers";

import FranchiseHorizontal from "./franchiseHorizontal.vue";
import FranchiseVertical from "./franchiseVertical.vue";

export default {
    components: {
        FranchiseHorizontal,
        FranchiseVertical
    },
    methods: {
        ...layoutMethods
    },
    created: () => {
        document.documentElement.setAttribute("data-layout", "horizontal");
    },
    data() {
        return {};
    },
    computed: {
        ...layoutComputed,
    },
    mounted() {
    }
};
</script>

<template>
    <div>
        <FranchiseHorizontal v-if="layoutType === 'horizontal'" :layout="layoutType">
            <slot />
        </FranchiseHorizontal>
         <FranchiseVertical v-if="layoutType === 'vertical' || layoutType === 'semibox'" :layout="layoutType">
            <slot />
        </FranchiseVertical>

    </div>
</template>
