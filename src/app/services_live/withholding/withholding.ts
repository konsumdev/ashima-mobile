import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';
import { HTTP } from '@ionic-native/http/ngx';

@Injectable({
    providedIn: 'root'
})
export class WithholdingProvider {

    apiUrl: string;
    contentType: any;

    constructor(
        public http: HttpClient,
        private httpr: HTTP
    ) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getWithholdingTax(page: string) {
        
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10'
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/withholding_tax', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    getWithholdingTaxFixed(page: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10'
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/withholding_tax/withholding_tax_fixed', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }
}
