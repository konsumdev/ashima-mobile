import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';

@Injectable({
    providedIn: 'root'
})
export class HoursProvider {
    contentType: string;
    apiUrl: API_CONFIG.API;

    constructor(public http: HttpClient) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    viewAttendanceLogs(page: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('page', page);
        postParams.append('limit', '10');

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/attendance', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    viewAttendancePending(page: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('page', page);
        postParams.append('limit', '10');
        postParams.append('status', 'pending');

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/attendance', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    viewAttendanceReject(page: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('page', page);
        postParams.append('limit', '10');
        postParams.append('status', 'reject');

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/attendance', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    getApproversNameAndStatus(timeinID: any, lastSource: any, status: any, fileDate: any, source: any) {
        
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('employee_time_in_id', timeinID);
        postParams.append('last_source', lastSource);
        postParams.append('time_in_status', status);
        postParams.append('change_log_date_filed', fileDate);
        postParams.append('source', source);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/emp_time_in/get_approvers_name_and_status', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    checkLocks() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/attendance/check_locks', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    addLogs(logDetails: any, split_id: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('new_employee_timein_date', logDetails.schedDate);
        postParams.append('employee_timein_date', logDetails.timeInDate);
        postParams.append('time_in', logDetails.timeIn);
        postParams.append('time_out', logDetails.timeOut);
        postParams.append('lunch_in', logDetails.lunchIn);
        postParams.append('lunch_out', logDetails.lunchOut);
        postParams.append('first_break_in', logDetails.firstBreakIn);
        postParams.append('first_break_out', logDetails.firstBreakOut);
        postParams.append('second_break_in', logDetails.secondBreakIn);
        postParams.append('second_break_out', logDetails.secondBreakOut);

        postParams.append('schedule_blocks_id', split_id);

        postParams.append('reason', logDetails.reason);
        postParams.append('flag_halfday', logDetails.halfday);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/attendance/add_logs', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    splitChecker(date: any) {
        // date must be in YYYY-MM-DD format
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('datetime', date);
        postParams.append('check_split', "1");
        postParams.append('is_change_log', "false");

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/attendance/check_split', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    changeLogs(emp_timein_id: any, logDetails: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('employee_timein', emp_timein_id);
        postParams.append('emp_schedule_date', logDetails.schedDate);

        postParams.append('time_in', logDetails.timeIn);
        postParams.append('time_out', logDetails.timeOut);
        postParams.append('lunch_in', logDetails.lunchIn);
        postParams.append('lunch_out', logDetails.lunchOut);
        postParams.append('first_break_in', logDetails.firstBreakIn);
        postParams.append('first_break_out', logDetails.firstBreakOut);
        postParams.append('second_break_in', logDetails.secondBreakIn);
        postParams.append('second_break_out', logDetails.secondBreakOut);

        postParams.append('reason', logDetails.reason);
        postParams.append('flag_halfday', logDetails.halfday);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/attendance/change_log', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    checkTotalHours(logDetails: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('employee_timein', logDetails.timeInId);
        postParams.append('emp_schedule_date', logDetails.schedDate);

        postParams.append('time_in', logDetails.timeIn);
        postParams.append('time_out', logDetails.timeOut);
        postParams.append('lunch_in', logDetails.lunchIn);
        postParams.append('lunch_out', logDetails.lunchOut);
        postParams.append('first_break_in', logDetails.firstBreakIn);
        postParams.append('first_break_out', logDetails.firstBreakOut);
        postParams.append('second_break_in', logDetails.secondBreakIn);
        postParams.append('second_break_out', logDetails.secondBreakOut);

        postParams.append('reason', logDetails.reason);
        postParams.append('flag_halfday', logDetails.halfday);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/attendance/check_total_hours', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    breakChecker(changeDate: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('datetime', changeDate);
        postParams.append('get_breaks', "1");

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/attendance/check_breaks', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    checkPayrollLock(date: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('start_date', date);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/attendance/if_payroll_is_locked', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    overtimeLogValidation(logs: any) {
        if (logs) {

            let er_msg: any = [];
            if (logs.startDate > logs.endDate) {
                er_msg.push('End Date should not be less than the start date.');
            }

            if (er_msg.length > 0) {
                let msg_er: string = '<ul>';
                for (let msg of er_msg) {
                    msg_er += '<li>' + msg +'</li>';
                }
                msg_er += '</ul>';

                return msg_er;
            } else {
                return false;
            }
        }

        return "Invalid log entries.";
    }

    logsValidation(logs: any) {
        if (logs) {
            
            let er_msg: any = [];
            if (logs.timeIn > logs.timeOut) {
                er_msg.push('Time out should not be less than the time in');
            }

            if (logs.lunchOut) {
                if ((logs.timeIn > logs.lunchOut)) {
                    if (er_msg.indexOf('Breaks should not be less than the time in.') == -1) {
                        er_msg.push('Breaks should not be less than the time in.');
                    }                    
                }
            }

            if (logs.lunchIn) {
                if ((logs.timeIn > logs.lunchIn)) {
                    if (er_msg.indexOf('Breaks should not be less than the time in.') == -1) {
                        er_msg.push('Breaks should not be less than the time in.');
                    }   
                }
            }

            if (logs.firstBreakIn) {
                if ((logs.timeIn > logs.firstBreakIn)) {
                    if (er_msg.indexOf('Breaks should not be less than the time in.') == -1) {
                        er_msg.push('Breaks should not be less than the time in.');
                    }   
                }
            }
            if (logs.firstBreakOut) {
                if ((logs.timeIn > logs.firstBreakOut)) {
                    if (er_msg.indexOf('Breaks should not be less than the time in.') == -1) {
                        er_msg.push('Breaks should not be less than the time in.');
                    }   
                }
            }
            if (logs.secondBreakIn) {
                if ((logs.timeIn > logs.secondBreakIn)) {
                    if (er_msg.indexOf('Breaks should not be less than the time in.') == -1) {
                        er_msg.push('Breaks should not be less than the time in2.');
                    }   
                }
            }
            if (logs.secondBreakOut) {
                if ((logs.timeIn > logs.secondBreakOut)) {
                    if (er_msg.indexOf('Breaks should not be less than the time in.') == -1) {
                        er_msg.push('Breaks should not be less than the time in1.');
                    }   
                }
            }

            //lunch
            if (logs.lunchOut > logs.lunchIn) {
                er_msg.push('Lunch in should not be less then than lunch out');
            }

            if ((logs.firstBreakOut > logs.firstBreakIn) || (logs.secondBreakOut > logs.secondBreakIn)) {
                er_msg.push('Break in should not be less than the break out');
            }

            if ((logs.timeOut < logs.lunchOut) || (logs.timeOut < logs.lunchIn)
            || (logs.timeOut < logs.firstBreakIn) || (logs.timeOut < logs.firstBreakOut)
            || (logs.timeOut < logs.secondBreakIn) || (logs.timeOut < logs.secondBreakOut)) {
                er_msg.push('Time out should not be less than the breaks.');
            }

            if (er_msg.length > 0) {
                let msg_er: string = '<ul>';
                for (let msg of er_msg) {
                    msg_er += '<li>' + msg +'</li>';
                }
                msg_er += '</ul>';

                return msg_er;
            } else {
                return false;
            }
        }
        
        return "Invalid log entries.";
    }

    clockIn(location: any) {
        
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };

        let postParams = new URLSearchParams();
        postParams.append('location', location);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/attendance/clock_in_no_foto', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    clockGuardSettings2(session: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };

        let postParams = new URLSearchParams();
        postParams.append('emp_id', session.empid);
        postParams.append('account_id', session.accountid);
        postParams.append('company_id', session.compId);
        postParams.append('psa_id', session.psa_id);
        postParams.append('cloud_id', session.cloudid);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/dashboard/get_clockin_settings', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    clockGuardSettings() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/dashboard/get_clockin_settings', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    getAllOvertime(page: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };

        let postParams = new URLSearchParams();
        postParams.append('status', '');
        postParams.append('page', page);
        postParams.append('limit', "10");

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/overtime', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    getApprovedOvertime(page: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };

        let postParams = new URLSearchParams();
        postParams.append('status', 'approved');
        postParams.append('page', page);
        postParams.append('limit', "10");

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/overtime', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    getPendingOvertime(page: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };

        let postParams = new URLSearchParams();
        postParams.append('status', 'pending');
        postParams.append('page', page);
        postParams.append('limit', "10");

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/overtime', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    /** Rejected Overtime **/
    getRejectedOvertime(page: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };

        let postParams = new URLSearchParams();
        postParams.append('status', 'reject');
        postParams.append('page', page);
        postParams.append('limit', "10");

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/overtime', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    getOvertimeApprovers(overtimeId: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };

        let postParams = new URLSearchParams();
        postParams.append('overtime_id', overtimeId);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/overtime/get_approvers', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    calculateOvertimeRequest(logDetails: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };

        let postParams = new URLSearchParams();
        postParams.append('start_date', logDetails.startDate);
        postParams.append('end_date', logDetails.endDate);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/overtime/calculate_overtime', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    checkPayrollLockOt(startDate: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };

        let postParams = new URLSearchParams();
        postParams.append('start_date', startDate);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/overtime/if_payroll_is_locked', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    submitOvertime(logs: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };

        let postParams = new URLSearchParams();
        postParams.append('start_date', logs.startDate);
        postParams.append('end_date', logs.endDate);
        postParams.append('purpose', logs.reason);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/overtime/apply_overtime', postParams.toString(), headers).subscribe(
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
