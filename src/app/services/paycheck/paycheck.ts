import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';

@Injectable({
    providedIn: 'root'
})
export class PaycheckProvider {
    apiUrl: any;
    contentType: any;

    constructor(public http: HttpClient) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getCurrentPayslip() {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('payroll_payslip_id', "");
        postParams.append('flag_payslip', "1");

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/payslip/payslip_detail_v2', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    getAllPayslip(page: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('page', page);
        postParams.append('limit', '10');

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/payslip/payslip_v2', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    getPayslipDetail(payslipId: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('payroll_payslip_id', payslipId);
        postParams.append('flag_payslip', "0");

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/payslip/payslip_detail_v2', postParams.toString(), headers).subscribe(
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
