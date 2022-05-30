import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';

@Injectable({
    providedIn: 'root'
})
export class LeavesProvider {
    contentType: string;
    apiUrl: API_CONFIG.API;

    constructor(public http: HttpClient) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getBalance() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/leave/get_leave_type', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    getLeaveHistory(page: string, type: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('page', page);
        postParams.append('limit', '10'); //lv_status
        postParams.append('lv_status', type);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/leave', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    checkWorkSchedule(startDate: any, endDate: any, ifPartial: any, leaveType: any, flexiHrs: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('end_date', endDate);
        postParams.append('flexi_hrs', flexiHrs);
        postParams.append('if_partial', ifPartial);
        postParams.append('leave_type', leaveType);
        postParams.append('start_date', startDate);
        postParams.append('check_work_schedule', "1");        

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/leave/get_work_schedule', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    checkPayrollLock(startDate: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('shift_date', startDate);
        postParams.append('if_payroll_is_locked', "1");

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/leave/if_payroll_is_locked', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    getTotalLeaveRequest(startDate: any, startTime: any, endDate: any, endTime: any, flexiHrs: any,
        ifNs: any, ifPartial: any, leaveType: any, lunchRequired: any) 
    {
        var ns = (ifNs) ? 'yes' : 'no';
        var partial = (ifPartial) ? 'yes' : 'no';
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('end_date', endDate);
        postParams.append('end_time', endTime);
        postParams.append('flexi_hrs', flexiHrs);
        postParams.append('if_NS', ns);
        postParams.append('if_partial', partial);
        postParams.append('leave_type', leaveType);
        postParams.append('lunch_hr_required', lunchRequired);
        postParams.append('start_date', startDate);
        postParams.append('start_time', startTime);
        postParams.append('tlr', "1");

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/leave/get_total_leaves', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    submitLeave(startDate: any, startTime: any, endDate: any, endTime: any, ifPartial: any, 
        leaveType: any, lunchRequired: any, leaveRequest: any, prevCredits: any,
        reason: any, reqDoc: any, uploadNumbers: any) {

            var partial = (ifPartial) ? 'Partial Day' : '';
            let headers =  {
                headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
                withCredentials: true
            };
            let postParams = new URLSearchParams();
            postParams.append('cont_tlr_hidden', leaveRequest);
            postParams.append('end_date', endDate);
            postParams.append('end_time', endTime);
            postParams.append('leave_request_type', partial);
            postParams.append('leave_type', leaveType);
            postParams.append('lunch_hr_required', lunchRequired);
            postParams.append('start_date', startDate);
            postParams.append('start_time', startTime);
            postParams.append('reason', reason);
            postParams.append('required_doc', '');
            postParams.append('upload_numbers', uploadNumbers);
            postParams.append('save_my_leave', "1");

            return new Promise((resolve, reject) => {
                this.http.post(this.apiUrl + '/api/leave/submit_leaves', postParams.toString(), headers).subscribe(
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
