(function () {
    const TenantPage = {
        init: () => {
            jQuery(document).on(
                "change",
                '#create-form input[name="name"]',
                TenantPage.generateSlug,
            );

            console.log(jQuery('#create-form input[name="name"]'));
        },
        generateSlug: function (e) {
            const name = $(this).val();
            const slug = TenantPage.slugify(name); 

            console.log('slug', slug)

            jQuery('#create-form input[name="slug"]').val(slug); 
        },
        slugify: function (text) {
            return text
                .toString()
                .toLowerCase()
                .trim()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/[^a-z0-9 -]/g, "")
                .replace(/\s+/g, "-")
                .replace(/-+/g, "-");
        },
    };

    jQuery(document).ready(function () {
        TenantPage.init();
    });
})();
