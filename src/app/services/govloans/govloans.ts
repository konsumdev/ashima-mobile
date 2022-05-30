import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';

@Injectable({
    providedIn: 'root'
})
export class GovloansProvider {
    contentType: string;
    apiUrl: string;

    constructor(public http: HttpClient) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getGovLoans() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/gov_loan', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    getThirdLoans() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/third_party_loan', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    getOtherDeductions(page: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('page', page);
        postParams.append('limit', '10');

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/other_deductions', postParams.toString(), headers).subscribe(
                data => {
                resolve(data);
                }, 
                error => {
                reject(error);
                }
            );
        });
    }
}
