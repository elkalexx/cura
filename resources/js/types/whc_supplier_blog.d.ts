export interface WhcSupplierBlog {
    id: number;
    offer_no: number;
    title: string;
    status: number;
    description: string;
    has_file: boolean;
    file_name: string;
    file_path: string;
    created_at_whc: string;
    updated_at_whc: string;
    created_at: string;
    updated_at: string;
    is_approved: boolean;
    is_brand: boolean;
    is_b_group_appr: boolean;
    is_sold: boolean;
    offer_title: string;
    supplier: string;
    offer_ext_status: number;

    whc_supplier_offer_blog_magento: WhcSupplierOfferBlogMagento;
}

export interface WhcSupplierOfferBlogMagento {
    id: number;
    whc_supplier_offer_blog_id: number;
    magento_blog_id: number;
    status: number;
    url_key: string;
    created_magento_at: string;
}
