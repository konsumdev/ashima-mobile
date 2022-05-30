// Put here any constant variables you might use anywhere in the app
// just import this file to wherever location you need this
// ex: import * as API_CONFIG from '../../providers/api-config';

// The API url
export enum API{
    VERSION = "3",
    URL = "http://sv01.ashima.ph:10010",
    BASE_URI = "http://sv01.ashima.ph:10010/"

    /* local */
    // URL = "http://payrollv2.local:8100",
    // BASE_URI = "http://payrollv2.local/"

    /* when testing on device using local api,
        use the EXTERNAL IP generated when using ionic serve */
    // URL = "http://10.48.11.142:8100",
    // BASE_URI = "http://10.48.11.142/"

    /* staging */
    // URL = "http://payrollmobile1.ashima.ph:9000",
    // BASE_URI = "http://payrollmobile1.ashima.ph:9000/"

    /* live */
    // URL = "http://sv01.ashima.ph:10010",
    // BASE_URI = "http://sv01.ashima.ph:10010/"
}

/**
 * Define content type, used for http post
 */
export const CONTENT_TYPE : string = 'application/x-www-form-urlencoded;charset=UTF-8';
// export const CONTENT_TYPE : string = 'application/json';