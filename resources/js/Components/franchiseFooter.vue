<template>
    <footer class="footer">
        <BContainer fluid>
            <BRow>
                <BCol cols="3">
                     <!-- {{ new Date().getFullYear() }} © {{$t("misoftwares")}} -->
                    <div class="text-sm-start  d-sm-block">
                      {{ footerContent1 }}
                    </div>
                </BCol>
                <BCol cols="5">
                    <!-- {{ new Date().getFullYear() }} © {{$t("misoftwares")}} -->
                    <div class="text-sm-center  d-sm-block">
                      {{ footerContent2 }}
                    </div>
                </BCol>
                <BCol cols="4">
                    <div class="text-sm-end  d-sm-block">
                        <!-- {{$t("design_and_develop_by_misoftwares")}} -->
                        App version <span class="badge bg-success-subtle text-success fs-12">1</span>
                    </div>
                </BCol>
            </BRow>
        </BContainer>
    </footer>
</template>


<script>

export default {
    
    data() {
        return {            
            footerContent1: window.footer_content1, // Access global footer data
            footerContent2: window.footer_content2, // Access global footer data
        };
    },
}
</script>
