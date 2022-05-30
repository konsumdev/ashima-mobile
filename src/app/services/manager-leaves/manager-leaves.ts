import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';

@Injectable({
    providedIn: 'root'
})
export class ManagerLeavesProvider {
    contentType: string;
    apiUrl: API_CONFIG.API;

    constructor(public http: HttpClient) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    leaveBalanceList(page: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('lv_status', "");
        postParams.append('page', page);
        postParams.append('limit', "10");

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/manager_leaves/leave_balance_list', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    allLeaveHistory(page: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('lv_status', "");
        postParams.append('page', page);
        postParams.append('limit', "10");

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/manager_leaves/leave_history_list', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    approvedLeaveHistory(page: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('lv_status', "");
        postParams.append('page', page);
        postParams.append('limit', "10");

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/manager_leaves/leave_history_approve', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    pendingLeaveHistory(page: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('lv_status', "");
        postParams.append('page', page);
        postParams.append('limit', "10");

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/manager_leaves/leave_history_pending', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    rejectLeaveHistory(page: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('lv_status', "");
        postParams.append('page', page);
        postParams.append('limit', "10");

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/manager_leaves/leave_history_reject', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    getApproversNameAndStatus(leaveId: any, status: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('employee_leaves_application_id', leaveId);
        postParams.append('leave_application_status', status);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/manager_leaves/get_approvers_name_and_status', postParams.toString(), headers).subscribe(
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
