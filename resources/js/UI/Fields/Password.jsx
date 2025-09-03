import { Eye, EyeOff, Check, X, Loader } from "lucide-react";
import React, { useMemo, useState } from "react";

export default function Password({ password, handleChange, validate=false, label='Password'}) {
    const [showPassword, setShowPassword] = useState(false);

      const passwordValidation = useMemo(() => {
        const pwd = password || "";
        const checks = {
          length: pwd.length >= 8,
          uppercase: /[A-Z]/.test(pwd),
          lowercase: /[a-z]/.test(pwd),
          number: /\d/.test(pwd),
          special: /[!@#$%^&*(),.?":{}|<>]/.test(pwd)
        };
    
        const validCount = Object.values(checks).filter(Boolean).length;
        let strength = 'Weak';
        let strengthColor = 'text-error';
        
        if (validCount >= 4) {
          strength = 'Strong';
          strengthColor = 'text-success';
        } else if (validCount >= 2) {
          strength = 'Medium';
          strengthColor = 'text-warning';
        }
    
        return { checks, strength, strengthColor, validCount };
      }, [password]);

    return (
        <div>
        <label className="block text-sm font-medium text-base-content mb-2">
            {label}
        </label>
        <div className="relative">
            <input
            type={showPassword ? "text" : "password"}
            name="password"
            value={password}
            onChange={handleChange}
            placeholder="Enter your password"
            className="input input-bordered w-full bg-base-200 border-base-300 focus:border-primary pr-12"
            required
            />
            <button
            type="button"
            onClick={() => setShowPassword(!showPassword)}
            className="absolute inset-y-0 right-0 pr-3 flex items-center text-base-content/50 hover:text-base-content"
            >
            {showPassword ? (
                <EyeOff className="h-5 w-5" />
            ) : (
                <Eye className="h-5 w-5" />
            )}
            </button>
        </div>
        {password && validate && (
                <div className="mt-3 space-y-2">
                  <div className="flex justify-between items-center">
                    <span className="text-xs text-base-content/70">
                      Password strength: <span className={`font-medium ${passwordValidation.strengthColor}`}>
                        {passwordValidation.strength}
                      </span>
                    </span>
                    <div className="flex gap-1">
                      {[1, 2, 3, 4, 5].map((level) => (
                        <div
                          key={level}
                          className={`w-2 h-2 rounded-full ${
                            level <= passwordValidation.validCount
                              ? passwordValidation.validCount >= 4
                                ? 'bg-success'
                                : passwordValidation.validCount >= 2
                                ? 'bg-warning'
                                : 'bg-error'
                              : 'bg-base-300'
                          }`}
                        />
                      ))}
                    </div>
                  </div>
                  
                  <div className="space-y-1">
                    <div className="flex items-center gap-2">
                      {passwordValidation.checks.length ? (
                        <Check className="w-3 h-3 text-success" />
                      ) : (
                        <X className="w-3 h-3 text-error" />
                      )}
                      <span className={`text-xs ${passwordValidation.checks.length ? 'text-success' : 'text-base-content/60'}`}>
                        At least 8 characters
                      </span>
                    </div>
                    
                    <div className="flex items-center gap-2">
                      {passwordValidation.checks.uppercase ? (
                        <Check className="w-3 h-3 text-success" />
                      ) : (
                        <X className="w-3 h-3 text-error" />
                      )}
                      <span className={`text-xs ${passwordValidation.checks.uppercase ? 'text-success' : 'text-base-content/60'}`}>
                        One uppercase letter
                      </span>
                    </div>
                    
                    <div className="flex items-center gap-2">
                      {passwordValidation.checks.lowercase ? (
                        <Check className="w-3 h-3 text-success" />
                      ) : (
                        <X className="w-3 h-3 text-error" />
                      )}
                      <span className={`text-xs ${passwordValidation.checks.lowercase ? 'text-success' : 'text-base-content/60'}`}>
                        One lowercase letter
                      </span>
                    </div>
                    
                    <div className="flex items-center gap-2">
                      {passwordValidation.checks.number ? (
                        <Check className="w-3 h-3 text-success" />
                      ) : (
                        <X className="w-3 h-3 text-error" />
                      )}
                      <span className={`text-xs ${passwordValidation.checks.number ? 'text-success' : 'text-base-content/60'}`}>
                        One number
                      </span>
                    </div>
                    
                    <div className="flex items-center gap-2">
                      {passwordValidation.checks.special ? (
                        <Check className="w-3 h-3 text-success" />
                      ) : (
                        <X className="w-3 h-3 text-error" />
                      )}
                      <span className={`text-xs ${passwordValidation.checks.special ? 'text-success' : 'text-base-content/60'}`}>
                        One special character (!@#$%^&*)
                      </span>
                    </div>
                  </div>
                </div>
              )}
        </div>
    );
}
